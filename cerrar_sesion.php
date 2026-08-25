<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['session_id']) && !empty($_SESSION['user_id'])) {
    include_once __DIR__ . '/bd.php';
    
    $session_id = $_SESSION['session_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conexion->prepare('UPDATE SesionesActivas SET Estado = 0 WHERE ID = ?');
    $stmt->execute([$session_id]);
}

$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;
?>