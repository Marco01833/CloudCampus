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
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de cursos — Punto Código</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

    <main class="wrap">

        <div class="page-header">
            <span class="eyebrow">// tu carrito</span>
            <h1>Carrito de cursos</h1>
            <p>Revisá los cursos seleccionados antes de continuar con el pago.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px; background: #fee2e2; border: 1px solid #fecaca; color: #991b1b;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="cart-layout">

            <div class="cart-items">

                <?php if (empty($cursos_carrito)): ?>

                    <div class="cart-empty">
                        <i class="fa-solid fa-cart-shopping" style="font-size:2rem; color: var(--muted); margin-bottom:1rem;"></i>
                        <p>Tu carrito de cursos está vacío.</p>
                        <a href="../Productos/index.php" class="btn btn-primary" style="margin-top:1rem;">Explorar cursos</a>
                    </div>

                <?php else: ?>

                    <?php foreach ($cursos_carrito as $curso): 
                        $profesor = trim(
                            ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellidos'] ?? '')
                        ) ?: 'Profesor';
                        $imagen = !empty($curso['Imagen']) 
                            ? '../Cursos_Usuario/Imagen/' . htmlspecialchars($curso['Imagen']) 
                            : 'https://placehold.co/160x120/EFE9E9/6E6864?text=Curso';
                    ?>
                    <div class="cart-item">
                        <img class="cart-item-img" src="<?= $imagen ?>" alt="Miniatura del curso">
                        <div class="cart-item-info">
                            <h3><?= htmlspecialchars($curso['Nombre']) ?></h3>
                            <div class="cart-item-meta">
                                <span><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($curso['Duracion'] ?? 'N/A') ?></span>
                                <span><i class="fa-solid fa-signal"></i> <?= htmlspecialchars($curso['nivel'] ?? 'N/A') ?></span>
                                <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($profesor) ?></span>
                            </div>
                        </div>
                        <div class="cart-item-price">
                            $<?= number_format($curso['Precio'], 2) ?>
                        </div>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="id_curso" value="<?= $curso['ID'] ?>">
                            <button type="submit" name="eliminar_curso" class="cart-item-remove" aria-label="Quitar curso">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>

                    <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; gap: 1rem;">
                        <a href="../Productos/index.php" class="btn btn-secondary" style="background: var(--surface); border: 1px solid var(--border); color: var(--ink); padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none;">
                            <i class="fa-solid fa-arrow-left"></i> Seguir comprando
                        </a>
                        <form method="post">
                            <button type="submit" name="vaciar_carrito" class="btn btn-outline-danger" style="background: transparent; border: 1px solid #e31e24; color: #e31e24; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer;" onclick="return confirm('¿Vaciar todo el carrito?')">
                                <i class="fa-solid fa-trash-can"></i> Vaciar carrito
                            </button>
                        </form>
                    </div>

                <?php endif; ?>

            </div>

            <aside class="cart-summary">
                <h2>Resumen del pedido</h2>

                <?php if (!empty($cursos_carrito)): ?>

                    <div class="cart-summary-row">
                        <span>Subtotal (<?= count($cursos_carrito) ?> cursos)</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($descuento_porcentaje > 0): ?>
                    <div class="cart-summary-row">
                        <span>Descuento (<?= $descuento_porcentaje ?>%)</span>
                        <span>-$<?= number_format($descuento_monto, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>

                    <hr style="border: 1px solid var(--border); margin: 1rem 0;">

                    <form method="post">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem;">Método de pago</label>
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                                    <input type="radio" name="metodo_pago" value="QR" checked> Pago con QR
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                                    <input type="radio" name="metodo_pago" value="Tarjeta"> Pago con tarjeta
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="confirmar_pago" class="btn btn-primary" style="width: 100%;">
                            Proceder al pago <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--muted);">
                        <i class="fa-solid fa-info-circle"></i> El plan <strong><?= htmlspecialchars($plan_usuario['Nombre'] ?? 'Básico') ?></strong> 
                        aplica un descuento del <?= $descuento_porcentaje ?>%.
                    </div>

                <?php else: ?>

                    <p style="color: var(--muted); font-size: 0.9rem;">Tu carrito está vacío.</p>

                <?php endif; ?>

            </aside>

        </div>

    </main>

</body>
</html>

<?php include("../footer.php"); ?>