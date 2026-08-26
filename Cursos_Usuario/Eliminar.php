<?php
include("../autenticacion.php");
include("../bd.php");
if (!isset($_POST['id'])) {
    header("Location: index.php?mensaje=ID de curso no proporcionado");
    exit;
}

$id_curso = (int)$_POST['id'];
$id_usuario = $_SESSION['user_id'];
$rol_usuario = $_SESSION['rol'] ?? 0;
$stmt = $conexion->prepare("SELECT ID, Imagen, Estado FROM Cursos WHERE ID = ?");
$stmt->execute([$id_curso]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    header("Location: index.php?mensaje=Curso no encontrado");
    exit;
}

$permiso = false;
if ($rol_usuario == 2) {
    $permiso = true;
} elseif ($rol_usuario == 3 && in_array($curso['Estado'], ['Pendiente', 'Rechazado'])) {
    $permiso = true;
}

if (!$permiso) {
    header("Location: index.php?mensaje=No tienes permiso para eliminar este curso");
    exit;
}

if (!empty($curso['Imagen']) && $curso['Imagen'] != 'default.jpg') {
    $ruta_imagen = "../Cursos/Imagen/" . $curso['Imagen'];
    if (file_exists($ruta_imagen)) {
        unlink($ruta_imagen);
    }
}

$stmt = $conexion->prepare("SELECT Tipo, Archivo FROM Contenido WHERE IDCurso = ?");
$stmt->execute([$id_curso]);
$contenidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($contenidos as $contenido) {
    if ($contenido['Tipo'] == 'video' || $contenido['Tipo'] == 'archivo') {
        $ruta = "../Contenido/" . ($contenido['Tipo'] == 'video' ? 'Video/' : 'Archivos/') . $contenido['Archivo'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
}

try {
    $stmt = $conexion->prepare("CALL sp_eliminar_curso(?, @mensaje)");
    $stmt->execute([$id_curso]);
    $stmt = $conexion->query("SELECT @mensaje AS mensaje");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $mensaje = $resultado['mensaje'];
    header("Location: index.php?mensaje=" . urlencode($mensaje));
    exit;
} catch (PDOException $e) {
    header("Location: index.php?mensaje=Error al eliminar: " . $e->getMessage());
    exit;
}
?>