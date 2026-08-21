<?php
include 'bd.php';
require_once __DIR__ . '/mail_config.php';

$mensaje = null;
$error = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $Correo = trim($_POST['Correo'] ?? '');
    if($Correo === ''){
        $error = 'Ingrese su correo.';
    } else {
        try {
            $stmt = $conexion->prepare('SELECT ID, Correo FROM Usuarios WHERE Correo = :Correo LIMIT 1');
            $stmt->execute([':Correo'=>$Correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
           
            $mensaje = 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.';
            if($user){
                $token = bin2hex(random_bytes(32));
                $ins = $conexion->prepare('INSERT INTO restablecer_contrasena (user_id, token) VALUES (:uid, :tok)');
                $ins->execute([':uid'=>$user['ID'], ':tok'=>$token]);

                $mail = crearMailer();
                $mail->addAddress($user['Correo']);
                $mail->isHTML(true);
                $mail->Subject = 'Restablecer contraseña - Cloud Campus';
                $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/restablecer.php?token=' . urlencode($token);
                $mail->Body = '<p>Hola,</p><p>Para restablecer tu contraseña haz clic en el siguiente enlace:</p><p><a href="' . $url . '">Restablecer contraseña</a></p><p>Si no solicitaste este cambio, ignora este mensaje.</p>';
                $mail->send();
            }
        } catch(Exception $e){
            $error = 'Ocurrió un error: ' . $e->getMessage();
        }
    }
}
?>
<div class="container mt-5">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">¿Olvidaste tu contraseña?</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="Correo" class="form-control" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Enviar enlace</button>
                        <a class="btn btn-link" href="index.php">Volver a iniciar sesión</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
