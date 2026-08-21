<?php
$servidor = "localhost";
$basededatos = "cloudcampus";
$usuario = "root";
$clave = "";
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$basededatos", $usuario, $clave);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex) {
    die("Error de conexión: " . $ex->getMessage());
}
?>