<?php
include 'bd.php';
include 'header.php';

$mensaje = null;
$error = null;
$token = $_GET['token'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$correo_reenvio = $_POST['correo_reenvio'] ?? '';
$correo_prefill = $_GET['correo'] ?? ''; // para prellenar el campo

if ($token !== '') {
    try {
        $sql = 'SELECT ve.id, ve.user_id, u.Verificado, ve.fecha_expiracion 
                FROM verificacion_email ve 
                INNER JOIN Usuarios u ON u.ID = ve.user_id 
                WHERE ve.token = :tok LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':tok' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Token inválido o ya utilizado.';
        } elseif ($row['Verificado'] == 1) {
            $mensaje = 'Tu cuenta ya estaba verificada. Puedes iniciar sesión.';
        } elseif (new DateTime() > new DateTime($row['fecha_expiracion'])) {
            $error = 'El enlace ha expirado. Solicita un nuevo código.';
            $stmt2 = $conexion->prepare('SELECT Correo FROM Usuarios WHERE ID = ?');
            $stmt2->execute([$row['user_id']]);
            $user = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $correo_expirado = $user['Correo'];
            }
        } else {
            $upd = $conexion->prepare('UPDATE Usuarios SET Verificado = 1 WHERE ID = :id');
            $upd->execute([':id' => $row['user_id']]);
            $del = $conexion->prepare('DELETE FROM verificacion_email WHERE id = :id');
            $del->execute([':id' => $row['id']]);
            $mensaje = '¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo']) && $codigo !== '') {
    try {
        $sql = 'SELECT ve.id, ve.user_id, u.Verificado, ve.fecha_expiracion 
                FROM verificacion_email ve 
                INNER JOIN Usuarios u ON u.ID = ve.user_id 
                WHERE ve.codigo = :cod LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':cod' => $codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Código inválido.';
        } elseif ($row['Verificado'] == 1) {
            $mensaje = 'Tu cuenta ya estaba verificada.';
        } elseif (new DateTime() > new DateTime($row['fecha_expiracion'])) {
            $error = 'El código ha expirado. Solicita un nuevo código.';
            $stmt2 = $conexion->prepare('SELECT Correo FROM Usuarios WHERE ID = ?');
            $stmt2->execute([$row['user_id']]);
            $user = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $correo_expirado = $user['Correo'];
            }
        } else {
            $upd = $conexion->prepare('UPDATE Usuarios SET Verificado = 1 WHERE ID = :id');
            $upd->execute([':id' => $row['user_id']]);
            $del = $conexion->prepare('DELETE FROM verificacion_email WHERE id = :id');
            $del->execute([':id' => $row['id']]);
            $mensaje = '¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reenviar'])) {
    $correo = trim($_POST['correo_reenvio'] ?? '');
    if ($correo === '') {
        $error = 'Ingresa tu correo electrónico.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT ID, Correo, Verificado FROM Usuarios WHERE Correo = :correo LIMIT 1');
            $stmt->execute([':correo' => $correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $error = 'No se encontró un usuario con ese correo.';
            } elseif ($user['Verificado'] == 1) {
                $mensaje = 'Tu cuenta ya está verificada. Puedes iniciar sesión.';
            } else {
                $del = $conexion->prepare('DELETE FROM verificacion_email WHERE user_id = :uid');
                $del->execute([':uid' => $user['ID']]);

                $nuevo_token = bin2hex(random_bytes(32));
                $nuevo_codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $ins = $conexion->prepare('INSERT INTO verificacion_email (user_id, token, codigo, fecha_expiracion) VALUES (:uid, :tok, :cod, :exp)');
                $ins->execute([':uid' => $user['ID'], ':tok' => $nuevo_token, ':cod' => $nuevo_codigo, ':exp' => $expiracion]);

                require_once __DIR__ . '/mail_config.php';
                $mail = crearMailer();
                $mail->addAddress($user['Correo']);
                $mail->isHTML(true);
                $mail->Subject = 'Nuevo código de verificación - Cloud Campus';

                $mail->Body = '
                <h2>Nuevo código de verificación</h2>
                <p>Hola,</p>
                <p>Has solicitado un nuevo código para verificar tu cuenta.</p>
                <p><strong>Código:</strong> ' . $nuevo_codigo . '</p>
                <p>El código expira en 24 horas.</p>
                <p>Si no solicitaste esto, ignora este mensaje.</p>
                ';
                $mail->send();

                $mensaje = 'Se ha enviado un nuevo código a tu correo. Revisa tu bandeja de entrada (y spam).';
            }
        } catch (Exception $e) {
            $error = 'Error al reenviar: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php if (strpos($mensaje, 'verificada') !== false): ?>
            <div class="mt-3">
                <a href="index.php" class="btn btn-primary">Iniciar sesión</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($mensaje && strpos($mensaje, 'verificada') !== false): ?>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Verificar cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($correo_expirado) && $correo_expirado): ?>
                            <div class="alert alert-warning">
                                El código ha expirado. Puedes solicitar uno nuevo.
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Código de verificación (6 dígitos)</label>
                                <input type="text" name="codigo" class="form-control" placeholder="Ej: 123456" maxlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Verificar
                            </button>
                            <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
                        </form>

                        <hr>

                        <h5><i class="bi bi-envelope"></i> ¿No recibiste el correo o expiró el código?</h5>
                        <p class="text-muted">Ingresa tu correo para recibir un <strong>nuevo código</strong> (diferente al anterior).</p>
                        <form method="post" class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Tu correo electrónico</label>
                                <input type="email" name="correo_reenvio" class="form-control" 
                                       value="<?= htmlspecialchars($correo_prefill ?? $correo_expirado ?? '') ?>" required>
                            </div>
                            <button type="submit" name="reenviar" class="btn btn-warning">
                                <i class="bi bi-arrow-repeat"></i> Reenviar nuevo código
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>