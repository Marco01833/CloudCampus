<?php
$url_base = "http://localhost/cloudcampus/"; 

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
?>
<!doctype html>
<html lang="es">
<head>
    <title>Cloud Campus</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<header>
    <nav class="navbar navbar-expand navbar-light bg-light">
        <div class="container">
            <ul class="nav navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $url_base ?>dashboard.php">Inicio</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): 
                    $id_usuario = $_SESSION['user_id'];
                    $sentencia = $conexion->prepare("SELECT IDRol FROM Usuarios WHERE ID = :id");
                    $sentencia->execute([':id' => $id_usuario]);
                    $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
                    $esAdmin = ($usuario && $usuario['IDRol'] == 2); 
                    if($esAdmin): ?>
                        <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Roles/rol.php">Roles</a></li> -->
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Usuarios/usuarios.php">Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>planes/index.php">Planes</a></li>
                       <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Permisos/permisos.php">Permisos</a></li> -->
                        <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>sesiones/historial.php">Sesiones activas</a></li> 
                       <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Cursos/index.php">Cursos</a></li> 
                       <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Contenido/index.php">Contenido</a></li> -->
                       <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Inscripciones/index.php">Inscripciones</a></li> -->
                    <?php endif; ?>
                   <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Cursos_Usuario/index.php">Mis Cursos</a></li> -->
                   <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Productos/index.php">Cursos</a></li> -->
                   <!-- <li class="nav-item"><a class="nav-link" href="<?= $url_base ?>Carrito/index.php">Carrito</a></li> -->
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['correo'])): ?>
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