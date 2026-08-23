<?php
include 'bd.php';

$token = $_GET['token'] ?? '';
$error = null;
$mensaje = null;
$valido = false;
$userId = null;

if($token === ''){
    $error = 'Token no proporcionado.';
} else {
    try {
        $sql = 'SELECT pr.id, pr.user_id FROM restablecer_contrasena pr WHERE pr.token = :tok LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':tok'=>$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            $valido = true;
            $userId = (int)$row['user_id'];
        } else {
            $error = 'Token inválido o expirado.';
        }
    } catch(Exception $e){
        $error = 'Error: ' . $e->getMessage();
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $token = $_POST['token'] ?? '';
    $pass1 = $_POST['Contrasena'] ?? '';
    $pass2 = $_POST['Contrasena2'] ?? '';
    if($pass1 === '' || $pass2 === ''){
        $error = 'Ingrese y confirme su nueva contraseña.';
    } elseif($pass1 !== $pass2){
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $sql = 'SELECT pr.id, pr.user_id FROM restablecer_contrasena pr WHERE pr.token = :tok LIMIT 1';
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':tok'=>$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row){
                $error = 'Token inválido o expirado.';
            } else {
                $hash = password_hash($pass1, PASSWORD_BCRYPT);
                $upd = $conexion->prepare('UPDATE Usuarios SET Contraseña = :p WHERE ID = :id');
                $upd->execute([':p'=>$hash, ':id'=>$row['user_id']]);
                $del = $conexion->prepare('DELETE FROM restablecer_contrasena WHERE id = :id');
                $del->execute([':id'=>$row['id']]);
                $mensaje = 'Contraseña actualizada. Ya puedes iniciar sesión.';
                $valido = false;
            }
        } catch(Exception $e){
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>
<div class="container mt-5">
    <?php if($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if($valido): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Restablecer contraseña</div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label class="form-label">Nueva contraseña</label>
                                <input type="password" name="Contrasena" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" name="Contrasena2" class="form-control" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <a class="btn btn-primary" href="index.php">Ir a iniciar sesión</a>
    <?php endif; ?>
</div>
