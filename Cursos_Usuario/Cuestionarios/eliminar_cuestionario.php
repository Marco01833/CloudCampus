<?php
include("../../autenticacion.php");
include("../../bd.php");
$id_cuestionario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_curso = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : 0;
if ($id_cuestionario <= 0 || $id_curso <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=ID inválido");
    exit;
}
$stmt = $conexion->prepare("SELECT IDUsuario FROM cursos WHERE ID = ?");
$stmt->execute([$id_curso]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
$esProfesor = ($curso && $curso['IDUsuario'] == $_SESSION['user_id']);

if (!$esProfesor) {
    header("Location: ../../Cursos_Usuario/contenido.php?id=$id_curso&mensaje=No tienes permiso");
    exit;
}

$stmt = $conexion->prepare("SELECT ID FROM Cuestionarios WHERE ID = ? AND IDCurso = ?");
$stmt->execute([$id_cuestionario, $id_curso]);
if (!$stmt->fetch()) {
    header("Location: ../../Cursos_Usuario/contenido.php?id=$id_curso&mensaje=Cuestionario no encontrado en este curso");
    exit;
}

try {
    $conexion->beginTransaction();
        $stmt = $conexion->prepare("DELETE FROM RespuestasUsuario WHERE IDIntento IN (SELECT ID FROM IntentosCuestionario WHERE IDCuestionario = ?)");
    $stmt->execute([$id_cuestionario]);
        $stmt = $conexion->prepare("DELETE FROM IntentosCuestionario WHERE IDCuestionario = ?");
    $stmt->execute([$id_cuestionario]);
        $stmt = $conexion->prepare("DELETE FROM Opciones WHERE IDPregunta IN (SELECT ID FROM Preguntas WHERE IDCuestionario = ?)");
    $stmt->execute([$id_cuestionario]);
        $stmt = $conexion->prepare("DELETE FROM Preguntas WHERE IDCuestionario = ?");
    $stmt->execute([$id_cuestionario]);
        $stmt = $conexion->prepare("DELETE FROM Cuestionarios WHERE ID = ?");
    $stmt->execute([$id_cuestionario]);
    $conexion->commit();
    header("Location: ../../Cursos_Usuario/contenido.php?id=$id_curso&mensaje=Cuestionario eliminado correctamente");
    exit;
} catch (Exception $e) {
    $conexion->rollBack();
    header("Location: ../../Cursos_Usuario/contenido.php?id=$id_curso&mensaje=Error al eliminar: " . $e->getMessage());
    exit;
}