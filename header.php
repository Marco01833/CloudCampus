<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url_base = $protocolo . "://" . $host . "/";

if ($host === 'localhost' || $host === '127.0.0.1') {
    $url_base = $protocolo . "://" . $host . "/cloudcampus/";
} else {
    $url_base = $protocolo . "://" . $host . "/";
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: ' . $url_base . 'index.php', true, 302);
    exit;
}

if (!isset($conexion) || !$conexion) {
    include_once __DIR__ . '/bd.php';
}

if (isset($conexion) && $conexion) {
    if (isset($_SESSION['session_id'])) {
        $stmt = $conexion->prepare("SELECT Estado FROM SesionesActivas WHERE ID = ? AND IDUsuario = ? LIMIT 1");
        $stmt->execute([$_SESSION['session_id'], $_SESSION['user_id']]);
        $sesion_activa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sesion_activa || $sesion_activa['Estado'] != 1) {
            session_destroy();
            header('Location: ' . $url_base . 'index.php', true, 302);
            exit;
        }
    }
}

$rol_usuario = null;
if (isset($_SESSION['user_id']) && isset($conexion) && $conexion) {
    $stmt = $conexion->prepare("SELECT IDRol FROM Usuarios WHERE ID = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $rol_usuario = (int)$usuario['IDRol'];
    }
}
define('ROL_ESTUDIANTE', 1);
define('ROL_ADMIN', 2);
define('ROL_PROFESOR', 3);
?>
<!doctype html>
<html lang="es">
<head>
    <title>Cloud Campus</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= $url_base ?>css/style.css">
</head>
<body>
<header>
    <nav class="navbar navbar-expand navbar-light bg-light">
        <div class="container">
            <ul class="nav navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $url_base ?>dashboard.php">Inicio</a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($rol_usuario === ROL_ADMIN): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Usuarios/usuarios.php">Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>planes/index.php">Planes</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Categorias/index.php">Categorías</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Cursos/index.php">Cursos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Auditoria/index.php">Auditoría</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Reportes/index.php">Reportes</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>sesiones/historial.php">Sesiones</a></li>

                    <?php elseif ($rol_usuario === ROL_PROFESOR): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Cursos_Usuario/index.php">Mis Cursos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>sesiones/historial.php">Sesiones</a></li>
                    <?php elseif ($rol_usuario === ROL_ESTUDIANTE): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Cursos_Usuario/index.php">Mis Cursos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Productos/index.php">Productos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Carrito/index.php">Carrito</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>planes/actualizar.php">Actualizar Plan</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Factura/index.php">Facturas</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Certificados/index.php">Mis certificados</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>sesiones/historial.php">Sesiones</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Datos_personales/index.php">Perfil</a></li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['correo'])): ?>
                    <li class="nav-item"><span class="navbar-text"><?= htmlspecialchars($_SESSION['correo']) ?></span></li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?= $url_base ?>cerrar_sesion.php">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </nav>
</header>
<main class="container mt-4">