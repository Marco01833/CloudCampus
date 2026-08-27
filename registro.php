<?php
include("bd.php");
require_once __DIR__ . '/mail_config.php';

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Correo = trim($_POST['Correo'] ?? '');
    $Contrasena = $_POST['Contrasena'] ?? '';
    $Metodo = $_POST['Metodo'] ?? 'enlace';
    $IdRol = 1;
    $IdPlan = 1;

    if ($Correo === '' || $Contrasena === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($Correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT 1 FROM Usuarios WHERE Correo = :Correo LIMIT 1');
            $stmt->execute([':Correo' => $Correo]);
            if ($stmt->fetch()) {
                $error = 'El correo ya está registrado.';
            } else {
                $hash = password_hash($Contrasena, PASSWORD_BCRYPT);
                $stmt = $conexion->prepare('INSERT INTO Usuarios (Correo, Contrasena, Estado, Verificado, IDRol, IDPlan, NumeroSesiones) VALUES (:Correo, :pass, 1, 0, :IdRol, :IdPlan, 2)');
                $stmt->execute([
                    ':Correo' => $Correo,
                    ':pass'   => $hash,
                    ':IdRol'  => $IdRol,
                    ':IdPlan' => $IdPlan
                ]);
                $userId = (int) $conexion->lastInsertId();

                $token = bin2hex(random_bytes(32));
                $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 minutes'));

                $stmt = $conexion->prepare('INSERT INTO verificacion_email (user_id, token, codigo, fecha_expiracion) VALUES (:uid, :tok, :cod, :exp)');
                $stmt->execute([':uid' => $userId, ':tok' => $token, ':cod' => $codigo, ':exp' => $expiracion]);

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $base = $protocol . '://' . $host . $path;
                $verificationUrl = $base . '/verificar.php?token=' . urlencode($token);

                $mail = crearMailer();
                $mail->addAddress($Correo);
                $mail->isHTML(true);
                $mail->Subject = 'Verifica tu cuenta - Cloud Campus';

                if ($Metodo === 'enlace') {
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
                            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
                            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
                            .content { padding: 25px 0; }
                            .content p { color: #555; font-size: 16px; line-height: 1.6; }
                            .btn { display: inline-block; background-color: #007bff; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                            .footer { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; color: #999; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>🎓 Cloud Campus</h1>
                            </div>
                            <div class="content">
                                <p>Hola,</p>
                                <p>Gracias por registrarte en <strong>Cloud Campus</strong>.</p>
                                <p>Haz clic en el botón para verificar tu cuenta:</p>
                                <p style="text-align: center;">
                                    <a href="' . $verificationUrl . '" class="btn">Verificar mi cuenta</a>
                                </p>
                                <p>Si no has solicitado esta cuenta, ignora este mensaje.</p>
                                <p>El enlace expirará en 24 horas.</p>
                            </div>
                            <div class="footer">
                                <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';
                    $mail->AltBody = "Hola,\n\nGracias por registrarte en Cloud Campus.\n\nPara verificar tu cuenta, haz clic en este enlace:\n" . $verificationUrl . "\n\nSi no has solicitado esta cuenta, ignora este mensaje.";
                } else {
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
                            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
                            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
                            .content { padding: 25px 0; }
                            .content p { color: #555; font-size: 16px; line-height: 1.6; }
                            .codigo-box { background-color: #f8f9fa; border: 2px dashed #007bff; padding: 15px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 8px; color: #007bff; }
                            .footer { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; color: #999; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>🎓 Cloud Campus</h1>
                            </div>
                            <div class="content">
                                <p>Hola,</p>
                                <p>Gracias por registrarte en <strong>Cloud Campus</strong>.</p>
                                <p>Usa el siguiente código para verificar tu cuenta:</p>
                                <div class="codigo-box">' . $codigo . '</div>
                                <p>Ingresa este código en la página de verificación.</p>
                                <p>Si no has solicitado esta cuenta, ignora este mensaje.</p>
                                
                            </div>
                            <div class="footer">
                                <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';
                    $mail->AltBody = "Hola,\n\nGracias por registrarte en Cloud Campus.\n\nTu código de verificación es: " . $codigo . "\n\nIngresa este código en la página de verificación.\n\nEl código expirará en 24 horas.";
                }

                $mail->send();

                if ($Metodo === 'codigo') {
                    header('Location: verificar.php?correo=' . urlencode($Correo));
                    exit;
                } else {
                    $mensaje = 'Registro exitoso. Te hemos enviado un enlace de verificación a tu correo.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error SQL: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Cloud Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Crear cuenta</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Correo electrónico</label>
                            <input type="email" name="Correo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contraseña</label>
                            <input type="password" name="Contrasena" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Método de verificación:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="Metodo" id="enlace" value="enlace" checked>
                                    <label class="form-check-label" for="enlace">Enviar enlace</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="Metodo" id="codigo" value="codigo">
                                    <label class="form-check-label" for="codigo">Enviar código</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Registrarme</button>
                        <a class="btn btn-link" href="index.php">¿Ya tienes cuenta? Inicia sesión</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>