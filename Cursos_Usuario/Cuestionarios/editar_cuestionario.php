<?php
include("../../autenticacion.php");
include("../../bd.php");

$cuestionario_id = isset($_GET['cuestionario_id']) ? (int)$_GET['cuestionario_id'] : 0;
if ($cuestionario_id <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Cuestionario no válido");
    exit;
}
$stmt = $conexion->prepare("SELECT c.*, co.IDCurso, co.Titulo as ContenidoTitulo 
                            FROM Cuestionarios c 
                            INNER JOIN Contenido co ON c.IDContenido = co.ID 
                            WHERE c.ID = ?");
$stmt->execute([$cuestionario_id]);
$cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cuestionario) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Cuestionario no encontrado");
    exit;
}
$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$stmt = $conexion->prepare("SELECT IDUsuario FROM cursos WHERE ID = ?");
$stmt->execute([$cuestionario['IDCurso']]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
$esProfesor = ($curso && $curso['IDUsuario'] == $_SESSION['user_id']);
if (!$esAdmin && !$esProfesor) {
    header("Location: ../../Cursos_Usuario/contenido.php?id=" . $cuestionario['IDCurso'] . "&mensaje=No tienes permiso");
    exit;
}
$mensaje_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Titulo = trim($_POST['Titulo'] ?? '');
    $Descripcion = trim($_POST['Descripcion'] ?? '');
    $TiempoLimite = (int)($_POST['TiempoLimite'] ?? 0);
    $CantidadPreguntas = (int)($_POST['CantidadPreguntas'] ?? 0);

    if (empty($Titulo)) {
        $mensaje_error = "El título es obligatorio.";
    } else {
        $sql = "UPDATE Cuestionarios SET 
                Titulo = ?, Descripcion = ?, TiempoLimite = ?, CantidadPreguntas = ? 
                WHERE ID = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$Titulo, $Descripcion, $TiempoLimite, $CantidadPreguntas, $cuestionario_id]);
        header("Location: gestionar_preguntas.php?cuestionario_id=$cuestionario_id&mensaje=Cuestionario actualizado correctamente");
        exit;
    }
}
include("../../header.php");
?>
<div class="container mt-4">
    <h2>Editar Cuestionario</h2>
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Contenido:</strong> <?= htmlspecialchars($cuestionario['ContenidoTitulo']) ?></p>
            <p><strong>Curso:</strong> <?= htmlspecialchars($cuestionario['IDCurso']) ?></p>
        </div>
    </div>
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="Titulo" class="form-label">Título del Cuestionario *</label>
            <input type="text" class="form-control" id="Titulo" name="Titulo" required value="<?= htmlspecialchars($cuestionario['Titulo']) ?>">
        </div>
        <div class="mb-3">
            <label for="Descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="3"><?= htmlspecialchars($cuestionario['Descripcion'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label for="TiempoLimite" class="form-label">Tiempo Límite (en minutos, 0 = sin límite)</label>
            <input type="number" class="form-control" id="TiempoLimite" name="TiempoLimite" min="0" value="<?= $cuestionario['TiempoLimite'] ?? 0 ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="gestionar_preguntas.php?cuestionario_id=<?= $cuestionario_id ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php include("../../footer.php"); ?>