<?php
include("../../autenticacion.php");
include("../../bd.php");

$id_contenido = isset($_GET['id_contenido']) ? (int)$_GET['id_contenido'] : 0;
if ($id_contenido <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Contenido no válido");
    exit;
}

$stmt = $conexion->prepare("SELECT c.*, cu.IDUsuario, cu.Nombre as CursoNombre 
                            FROM Contenido c 
                            INNER JOIN cursos cu ON c.IDCurso = cu.ID 
                            WHERE c.ID = ?");
$stmt->execute([$id_contenido]);
$contenido = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$contenido) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Contenido no encontrado");
    exit;
}

$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($contenido['IDUsuario'] == $_SESSION['user_id']);
if (!$esAdmin && !$esProfesor) {
    header("Location: ../../Cursos_Usuario/contenido.php?id=" . $contenido['IDCurso'] . "&mensaje=No tienes permiso");
    exit;
}

$mensaje_error = '';
$Titulo = $Descripcion = '';
$TiempoLimite = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Titulo = trim($_POST['Titulo'] ?? '');
    $Descripcion = trim($_POST['Descripcion'] ?? '');
    $TiempoLimite = (int)($_POST['TiempoLimite'] ?? 0);

    if (empty($Titulo)) {
        $mensaje_error = "El título es obligatorio.";
    } else {
        $sql = "INSERT INTO Cuestionarios (IDCurso, IDContenido, IDCreador, Titulo, Descripcion, CantidadPreguntas, TiempoLimite) 
                VALUES (?, ?, ?, ?, ?, 0, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            $contenido['IDCurso'],
            $id_contenido,
            $_SESSION['user_id'],
            $Titulo,
            $Descripcion,
            $TiempoLimite
        ]);
        $cuestionario_id = $conexion->lastInsertId();
        header("Location: gestionar_preguntas.php?cuestionario_id=$cuestionario_id&mensaje=Cuestionario creado correctamente");
        exit;
    }
}

include("../../header.php");
?>
<div class="container mt-4">
    <h2>Crear Cuestionario para Contenido</h2>
    <div class="card mb-4">
        <div class="card-body">
            <p><strong>Contenido:</strong> <?= htmlspecialchars($contenido['Titulo']) ?></p>
            <p><strong>Curso:</strong> <?= htmlspecialchars($contenido['CursoNombre'] ?? 'Curso no encontrado') ?></p>
        </div>
    </div>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="Titulo" class="form-label">Título del Cuestionario *</label>
            <input type="text" class="form-control" id="Titulo" name="Titulo" required value="<?= htmlspecialchars($Titulo) ?>">
        </div>
        <div class="mb-3">
            <label for="Descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="Descripcion" name="Descripcion" rows="3"><?= htmlspecialchars($Descripcion) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="TiempoLimite" class="form-label">Tiempo Límite (en minutos, 0 = sin límite)</label>
            <input type="number" class="form-control" id="TiempoLimite" name="TiempoLimite" min="0" value="<?= $TiempoLimite ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Guardar Cuestionario</button>
            <a href="../../Cursos_Usuario/contenido.php?id=<?= $contenido['IDCurso'] ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php include("../../footer.php"); ?>