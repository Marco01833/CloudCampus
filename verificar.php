<?php
include 'bd.php';

$mensaje = null;
$error = null;
$token = $_GET['token'] ?? '';

if($token === ''){
    $error = 'Token no proporcionado.';
} else {
    try {
        $sql = 'SELECT ve.id, ve.user_id, u.Verificado FROM verificacion_email ve INNER JOIN Usuarios u ON u.ID = ve.user_id WHERE ve.token = :tok LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':tok'=>$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row){
            $error = 'Token inválido o ya utilizado.';
        } else {
            if((int)$row['Verificado'] !== 1){
                $upd = $conexion->prepare('UPDATE Usuarios SET Verificado = 1 WHERE ID = :id');
                $upd->execute([':id'=>$row['user_id']]);
            }
            $del = $conexion->prepare('DELETE FROM verificacion_email WHERE id = :id');
            $del->execute([':id'=>$row['id']]);
            $mensaje = 'Cuenta verificada correctamente. Ya puedes iniciar sesión.';
        }
    } catch(Exception $e){
        $error = 'Error al verificar: ' . $e->getMessage();
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
    <a class="btn btn-primary" href="index.php">Ir a iniciar sesión</a>
</div>