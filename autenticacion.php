<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include_once __DIR__ . '/bd.php';
if (!empty($_SESSION['session_id'])) {
    $stmt = $conexion->prepare('SELECT 1 FROM SesionesActivas WHERE ID = ? AND Estado = 1 AND IDUsuario = ?');
    $stmt->execute([$_SESSION['session_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
?>
