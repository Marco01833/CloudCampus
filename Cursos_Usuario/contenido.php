<?php
session_start();
include("../bd.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$id_curso = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_curso <= 0) {
    header("Location: index.php");
    exit;
}
$sql = "SELECT 
            c.ID, 
            c.Nombre, 
            c.Descripcion, 
            c.Imagen, 
            c.Precio, 
            c.IDUsuario,
            c.nivel,
            cat.Nombre AS CategoriaNombre
        FROM cursos c
        LEFT JOIN categoria cat ON c.IDCategoria = cat.IDCategoria
        WHERE c.ID = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_curso]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
$sql_profesor = "SELECT dp.Nombre, dp.Apellidos 
                 FROM DatosPersonales dp 
                 WHERE dp.IDUsuario = ?";
$stmt_profesor = $conexion->prepare($sql_profesor);
$stmt_profesor->execute([$curso['IDUsuario']]);
$profesor = $stmt_profesor->fetch(PDO::FETCH_ASSOC);
$nombre_profesor = trim(($profesor['Nombre'] ?? '') . ' ' . ($profesor['Apellidos'] ?? ''));
if (empty($nombre_profesor)) {
    $sql_correo = "SELECT Correo FROM Usuarios WHERE ID = ?";
    $stmt_correo = $conexion->prepare($sql_correo);
    $stmt_correo->execute([$curso['IDUsuario']]);
    $usuario = $stmt_correo->fetch(PDO::FETCH_ASSOC);
    $nombre_profesor = $usuario['Correo'] ?? 'Profesor';
}
$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($curso['IDUsuario'] == $_SESSION['user_id']);
$puedeEditar = $esAdmin || $esProfesor; 

$mostrar_precio = true;
if ($rol_usuario == 1) { 
    $sql_inscripcion = "SELECT Estado FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ?";
    $stmt_inscripcion = $conexion->prepare($sql_inscripcion);
    $stmt_inscripcion->execute([$_SESSION['user_id'], $id_curso]);
    $inscripcion = $stmt_inscripcion->fetch(PDO::FETCH_ASSOC);
    if ($inscripcion && $inscripcion['Estado'] == 1) {
        $mostrar_precio = false; 
    }
}

if (isset($_GET['txtID'])) {
    $txtID = (int)$_GET['txtID'];
    $sentencia = $conexion->prepare("SELECT Archivo, Tipo FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    if ($registro) {
        $archivo = $registro['Archivo'];
        $tipo = $registro['Tipo'];
        if ($tipo == 'video' && !empty($archivo) && file_exists("Video/".$archivo)) {
            unlink("Video/".$archivo);
        } elseif ($tipo == 'archivo' && !empty($archivo) && file_exists("Archivos/".$archivo)) {
            unlink("Archivos/".$archivo);
        }
    }
    $sentencia = $conexion->prepare("DELETE FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location: contenido.php?id=$id_curso&mensaje=Contenido eliminado");
    exit;
}

include("../header.php");
$mensaje = $_GET['mensaje'] ?? '';
?>
<div class="container mt-4">
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($mensaje_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><?= htmlspecialchars($curso['Nombre']) ?></h4>
            <?php if ($esProfesor): // Solo el profesor dueño del curso ve el botón de editar curso ?>
                <a href="../Cursos/editar.php?txtID=<?= $id_curso ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil-square"></i> Editar curso
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($curso['Imagen'])): ?>
                <div class="text-center mb-4">
                    <img src="../Cursos_Usuario/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                         class="img-fluid rounded" 
                         alt="<?= htmlspecialchars($curso['Nombre']) ?>"
                         style="max-height: 300px;">
                </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <h5>Profesor:</h5>
                <span><?= htmlspecialchars($nombre_profesor) ?></span>
            </div>

            <div class="mb-3">
                <h5>Categoría:</h5>
                <span><?= htmlspecialchars($curso['CategoriaNombre'] ?? 'Sin categoría') ?></span>
            </div>
            <div class="mb-3">
                <h5>Nivel:</h5>
                <span><?= htmlspecialchars($curso['nivel'] ?? 'Básico') ?></span>
            </div>
            <?php if ($mostrar_precio): ?>
                <div class="mb-2">
                    <h5>Precio:</h5>
                    <span>$<?= number_format($curso['Precio'], 2) ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <h5>Descripción del curso:</h5>
                <p class="lead"><?= nl2br(htmlspecialchars($curso['Descripcion'])) ?></p>
            </div>
<div class="mt-4">
    <h5>Cuestionarios del Curso</h5>
    <?php
    $stmt = $conexion->prepare("SELECT * FROM Cuestionarios WHERE IDCurso = ? ORDER BY ID");
    $stmt->execute([$id_curso]);
    $cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($cuestionarios)): ?>
        <p class="text-muted">No hay cuestionarios aún.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($cuestionarios as $c): ?>
                <li>
                    <a href="Cuestionarios/cuestionario.php?cuestionario_id=<?= $c['ID'] ?>"><?= htmlspecialchars($c['Titulo']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($rol_usuario == 1): ?>
        <a href="Cuestionarios/calificaciones_estudiante.php?id_curso=<?= $id_curso ?>" class="btn btn-info">Ver Mis Calificaciones</a>
    <?php endif; ?>
    <?php if ($esProfesor || $esAdmin): ?>
        <a href="Cuestionarios/calificaciones_profesor.php?id_curso=<?= $id_curso ?>" class="btn btn-secondary">Ver Calificaciones de Estudiantes</a>
    <?php endif; ?>
</div>
        </div>
        <div class="card-body">
            <?php include(__DIR__ . "/vercontenido.php"); ?>
        </div>
    </div>
</div>
<?php include("../footer.php"); ?>