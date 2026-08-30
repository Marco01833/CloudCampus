<?php
include("../autenticacion.php");
include("../bd.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_curso = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$curso = null;
$plan_usuario = null;
$profesor_nombre_completo = '';
$user_id = $_SESSION['user_id'];
$stmt_user = $conexion->prepare("SELECT IDPlan FROM Usuarios WHERE ID = ?");
$stmt_user->execute([$user_id]);
$usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
if ($usuario) {
    $plan_usuario = $usuario['IDPlan'];
} else {
    $error = "No se pudo obtener la información de tu cuenta.";
}

if ($id_curso > 0 && !$error) {
    $stmt_curso = $conexion->prepare("
        SELECT c.*, 
               u.ID AS profesor_id,
               dp.Nombre AS profesor_nombre,
               dp.Apellidos AS profesor_apellidos
        FROM cursos c
        JOIN Usuarios u ON c.IDUsuario = u.ID
        LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
        WHERE c.ID = ?
    ");
    $stmt_curso->execute([$id_curso]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        $error = "El curso seleccionado no existe o no está disponible.";
    } else {
        $profesor_nombre_completo = trim(
            ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellidos'] ?? '')
        );
        if (empty($profesor_nombre_completo)) {
            $profesor_nombre_completo = 'Profesor';
        }
    }
} elseif (!$error) {
    $error = "No se ha especificado ningún curso para comprar.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    if (empty($metodo_pago)) {
        $error = "Debes seleccionar un método de pago.";
    } elseif (!$curso) {
        $error = "No se encontró el curso para la compra.";
    } else {
        try {
            $check = $conexion->prepare("SELECT ID FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ?");
            $check->execute([$user_id, $curso['ID']]);
            $existe = $check->fetch();

            if ($existe) {
                $update = $conexion->prepare("UPDATE Inscripciones SET Estado = 1 WHERE IDUsuario = ? AND IDCurso = ?");
                $update->execute([$user_id, $curso['ID']]);
                $_SESSION['mensaje_compra'] = "¡Curso reactivado! Ahora tienes acceso nuevamente.";
            } else {
                $sql = "INSERT INTO Inscripciones 
                        (IDUsuario, IDCurso, FechaInscripcion, Estado, Precio, Metodo, IDPlan) 
                        VALUES (?, ?, NOW(), 1, ?, ?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    $user_id,
                    $curso['ID'],
                    $curso['Precio'],
                    $metodo_pago,
                    $plan_usuario
                ]);
                $_SESSION['mensaje_compra'] = "¡Inscripción completada con éxito! Ahora puedes acceder al curso desde 'Mis Cursos'.";
            }

            header('Location: ../Productos/index.php');
            exit;

        } catch (PDOException $e) {
            $error = "Error al procesar la compra: " . $e->getMessage();
        }
    }
}

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4">Carrito de Compra</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($curso && !$error): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <?php if (!empty($curso['Imagen'])): ?>
                                <img src="../Cursos_Usuario/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                                     class="img-fluid rounded-start" style="height: 100%; object-fit: cover;" 
                                     alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100%;">
                                    <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($curso['Nombre']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($curso['Descripcion'] ?? 'Sin descripción') ?></p>
                                <ul class="list-unstyled">
                                    <li><strong>Profesor:</strong> <?= htmlspecialchars($profesor_nombre_completo) ?></li>
                                    <li><strong>Precio:</strong> $<?= number_format($curso['Precio'], 2) ?></li>
                                    <li><strong>Tu plan actual:</strong> <?= htmlspecialchars($plan_usuario) ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Confirmar compra</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Selecciona método de pago</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="qr" value="QR" checked>
                                    <label class="form-check-label" for="qr">Pago con QR</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta" value="Tarjeta">
                                    <label class="form-check-label" for="tarjeta">Pago con tarjeta</label>
                                </div>
                            </div>
                            <button type="submit" name="confirmar_compra" class="btn btn-success w-100">
                                <i class="bi bi-cart-check"></i> Finalizar compra
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!$error): ?>
        <p class="text-muted">No hay curso seleccionado para comprar.</p>
    <?php endif; ?>
</div>

<?php include("../footer.php"); ?>