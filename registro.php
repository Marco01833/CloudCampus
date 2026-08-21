<?php
include("bd.php");
require_once __DIR__ . '/mail_config.php';

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Correo = trim($_POST['Correo'] ?? '');
    $Contraseña = $_POST['Contraseña'] ?? '';
    $IdRol = 1; 
    $IdPlan = 1; 

    if ($Correo === '' || $Contraseña === '') {
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
                $hash = password_hash($Contraseña, PASSWORD_BCRYPT);
                $stmt = $conexion->prepare('INSERT INTO Usuarios (Correo, Contraseña, Estado, Verificado, IDRol, IDPlan) VALUES (:Correo, :pass, 1, 0, :IdRol, :IdPlan)');
                $stmt->execute([
                    ':Correo' => $Correo,
                    ':pass'   => $hash,
                    ':IdRol'  => $IdRol,
                    ':IdPlan' => $IdPlan
                ]);
                $userId = (int) $conexion->lastInsertId();

                $token = bin2hex(random_bytes(32));
                $stmt = $conexion->prepare('INSERT INTO verificacion_email (user_id, token) VALUES (:uid, :tok)');
                $stmt->execute([':uid' => $userId, ':tok' => $token]);

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $base = $protocol . '://' . $host . $path;
                $verificationUrl = $base . '/verificar.php?token=' . urlencode($token);

                $mail = crearMailer();
                $mail->addAddress($Correo);
                $mail->isHTML(true);
                $mail->Subject = 'Verifica tu cuenta - Cloud Campus';

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
                            <p>Gracias por registrarte en <strong>Cloud Campus</strong>. Para completar tu registro y activar tu cuenta, por favor verifica tu dirección de correo electrónico haciendo clic en el botón de abajo.</p>
                            <p style="text-align: center;">
                                <a href="' . $verificationUrl . '" class="btn" style="background-color: #007bff; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; display: inline-block;">Verificar mi cuenta</a>
                            </p>
                            <p>Si no has solicitado esta cuenta, puedes ignorar este mensaje.</p>
                            <p>El enlace expirará en 24 horas.</p>
                        </div>
                        <div class="footer">
                            <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            <p>Este es un correo automático, por favor no respondas.</p>
                        </div>
                    </div>
                </body>
                </html>
                ';

                $mail->AltBody = "Hola,\n\nGracias por registrarte en Cloud Campus. Para activar tu cuenta, copia el siguiente enlace en tu navegador:\n\n" . $verificationUrl . "\n\nSi no has solicitado esta cuenta, ignora este mensaje.\n\n© 2025 Cloud Campus";

                $mail->send();
                $mensaje = 'Registro exitoso. Te enviamos un correo para verificar tu cuenta.';
            }
        } catch (PDOException $e) {
            $error = 'Error SQL: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<div class="container mt-5">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Crear cuenta</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="Correo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="Contraseña" class="form-control" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Registrarme</button>
                        <a class="btn btn-link" href="index.php">¿Ya tienes cuenta? Inicia sesión</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>