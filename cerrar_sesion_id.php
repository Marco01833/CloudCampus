<?php
include("autenticacion.php");
include("bd.php");

$id_sesion = $_GET['id'] ?? 0;
$mensaje = '';

if($id_sesion && is_numeric($id_sesion)) {
    if($id_sesion == $_SESSION['session_id']) {
        $mensaje = 'No puedes cerrar tu propia sesión desde aquí.';
    } else {
        $stmt = $conexion->prepare("UPDATE SesionesActivas SET Estado = 0 WHERE ID = ?");
        $stmt->execute([$id_sesion]);
        if($stmt->rowCount() > 0) {
            $mensaje = 'Sesión cerrada correctamente.';
        } else {
            $mensaje = 'No se encontró la sesión o ya estaba cerrada.';
        }
    }
} else {
    $mensaje = 'ID de sesión no válido.';
}

header("Location: sesiones/historial.php?mensaje=" . urlencode($mensaje));
exit;
?>