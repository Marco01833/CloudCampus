<?php
include("../autenticacion.php");
include("../bd.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];
$error = null;
$mensaje = null;
$carrito = $_SESSION['carrito'] ?? [];

$stmt_plan = $conexion->prepare("SELECT p.ID, p.Nombre, p.Descuento FROM Usuarios u JOIN Planes p ON u.IDPlan = p.ID WHERE u.ID = ?");
$stmt_plan->execute([$user_id]);
$plan_usuario = $stmt_plan->fetch(PDO::FETCH_ASSOC);
$descuento_porcentaje = $plan_usuario['Descuento'] ?? 0; 

$cursos_carrito = [];
$subtotal = 0;
if (!empty($carrito)) {
    $placeholders = implode(',', array_fill(0, count($carrito), '?'));
    $stmt = $conexion->prepare("
        SELECT c.*, 
               u.ID AS profesor_id,
               dp.Nombre AS profesor_nombre,
               dp.Apellidos AS profesor_apellidos
        FROM cursos c
        JOIN Usuarios u ON c.IDUsuario = u.ID
        LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
        WHERE c.ID IN ($placeholders) AND c.Estado = 'Aprobado'
    ");
    $stmt->execute($carrito);
    $cursos_carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $subtotal = array_sum(array_column($cursos_carrito, 'Precio'));
}

$descuento_monto = ($descuento_porcentaje > 0) ? ($subtotal * $descuento_porcentaje / 100) : 0;
$total = $subtotal - $descuento_monto;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar_curso'])) {
        $id_eliminar = (int)$_POST['id_curso'];
        if (($key = array_search($id_eliminar, $_SESSION['carrito'])) !== false) {
            unset($_SESSION['carrito'][$key]);
            $_SESSION['carrito'] = array_values($_SESSION['carrito']); 
        }
        header('Location: index.php');
        exit;
    }

    if (isset($_POST['vaciar_carrito'])) {
        $_SESSION['carrito'] = [];
        header('Location: index.php');
        exit;
    }

    if (isset($_POST['confirmar_pago'])) {
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        if (empty($metodo_pago)) {
            $error = "Debes seleccionar un método de pago.";
        } elseif (empty($cursos_carrito)) {
            $error = "El carrito está vacío.";
        } else {
            try {
                $conexion->beginTransaction();

                $sql_factura = "INSERT INTO Facturas (IDUsuario, Fecha, Total, Estado, MetodoPago) 
                               VALUES (?, NOW(), ?, 1, ?)";
                $stmt_factura = $conexion->prepare($sql_factura);
                $stmt_factura->execute([$user_id, $total, $metodo_pago]);
                $factura_id = $conexion->lastInsertId();

                foreach ($cursos_carrito as $curso) {
                    $descuento_curso = ($curso['Precio'] / $subtotal) * $descuento_monto;

                    $sql_detalle = "INSERT INTO DetalleFactura 
                                   (IDFactura, PrecioUnidad, Descuento, TipoCompra, IDReferencia) 
                                   VALUES (?, ?, ?, 'curso', ?)";
                    $stmt_detalle = $conexion->prepare($sql_detalle);
                    $stmt_detalle->execute([$factura_id, $curso['Precio'], $descuento_curso, $curso['ID']]);

                    $check = $conexion->prepare("SELECT ID FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ?");
                    $check->execute([$user_id, $curso['ID']]);
                    if ($check->fetch()) {
                        $update = $conexion->prepare("UPDATE Inscripciones SET Estado = 1 WHERE IDUsuario = ? AND IDCurso = ?");
                        $update->execute([$user_id, $curso['ID']]);
                    } else {
                        $sql_inscripcion = "INSERT INTO Inscripciones 
                                           (IDUsuario, IDCurso, FechaInscripcion, Estado, Precio, Metodo, IDPlan) 
                                           VALUES (?, ?, NOW(), 1, ?, ?, ?)";
                        $stmt_inscripcion = $conexion->prepare($sql_inscripcion);
                        $stmt_inscripcion->execute([
                            $user_id,
                            $curso['ID'],
                            $curso['Precio'],
                            $metodo_pago,
                            $plan_usuario['ID']
                        ]);
                    }
                }

                $conexion->commit();

                $_SESSION['carrito'] = [];
                $_SESSION['mensaje_compra'] = "¡Compra realizada con éxito! Revisa tus cursos en 'Mis Cursos'.";
                header('Location: ../Productos/index.php');
                exit;

            } catch (PDOException $e) {
                $conexion->rollBack();
                $error = "Error al procesar la compra: " . $e->getMessage();
            }
        }
    }
}

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4"> Carrito de Compra</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($cursos_carrito)): ?>
        <div class="alert alert-info">
            Tu carrito está vacío. 
            <a href="../Productos/index.php" class="alert-link">Explora nuestros cursos</a>.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <?php foreach ($cursos_carrito as $curso): 
                    $profesor_nombre = trim(
                        ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellidos'] ?? '')
                    ) ?: 'Profesor';
                ?>
                <div class="card mb-3">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-3">
                            <?php if (!empty($curso['Imagen'])): ?>
                                <img src="../Cursos_Usuario/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                                     class="img-fluid rounded-start" style="height: 120px; object-fit: cover; width: 100%;" 
                                     alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                    <i class="bi bi-book text-muted" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title"><?= htmlspecialchars($curso['Nombre']) ?></h5>
                                        <p class="card-text small text-muted">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($profesor_nombre) ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <p class="fw-bold text-primary">$<?= number_format($curso['Precio'], 2) ?></p>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="id_curso" value="<?= $curso['ID'] ?>">
                                            <button type="submit" name="eliminar_curso" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between mt-3">
                    <a href="../Productos/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Seguir comprando
                    </a>
                    <form method="post">
                        <button type="submit" name="vaciar_carrito" class="btn btn-outline-danger" 
                                onclick="return confirm('¿Vaciar todo el carrito?')">
                            <i class="bi bi-cart-x"></i> Vaciar carrito
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Resumen del pedido</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="d-flex justify-content-between">
                                <span>Subtotal (<?= count($cursos_carrito) ?> cursos)</span>
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </li>
                            <?php if ($descuento_porcentaje > 0): ?>
                                <li class="d-flex justify-content-between text-success">
                                    <span>Descuento (<?= $descuento_porcentaje ?>%)</span>
                                    <span>-$<?= number_format($descuento_monto, 2) ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="d-flex justify-content-between fw-bold fs-5 mt-2 pt-2 border-top">
                                <span>Total</span>
                                <span>$<?= number_format($total, 2) ?></span>
                            </li>
                        </ul>

                        <hr>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Método de pago</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="qr" value="QR" checked>
                                    <label class="form-check-label" for="qr">Pago con QR</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta" value="Tarjeta">
                                    <label class="form-check-label" for="tarjeta">Pago con tarjeta</label>
                                </div>
                            </div>
                            <button type="submit" name="confirmar_pago" class="btn btn-success w-100">
                                <i class="bi bi-credit-card"></i> Proceder al pago
                            </button>
                        </form>

                        <div class="mt-3 small text-muted">
                            <i class="bi bi-info-circle"></i> El plan <strong><?= htmlspecialchars($plan_usuario['Nombre'] ?? 'Básico') ?></strong> 
                            aplica un descuento del <?= $descuento_porcentaje ?>%.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("../footer.php"); ?>