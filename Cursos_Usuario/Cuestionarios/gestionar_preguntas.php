<?php
include("../../autenticacion.php");
include("../../bd.php");

$cuestionario_id = isset($_GET['cuestionario_id']) ? (int)$_GET['cuestionario_id'] : 0;
if ($cuestionario_id <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Cuestionario no válido");
    exit;
}
$stmt = $conexion->prepare("SELECT c.*, co.IDCurso 
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

$mensaje = $_GET['mensaje'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'agregar_pregunta') {
        $Enunciado = trim($_POST['Enunciado'] ?? '');
        $Tipo = $_POST['Tipo'] ?? 'opcion_unica';
        $Puntaje = (float)($_POST['Puntaje'] ?? 1.00);
        if (!empty($Enunciado)) {
            $sql = "INSERT INTO Preguntas (IDCuestionario, Enunciado, Tipo, Puntaje) VALUES (?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$cuestionario_id, $Enunciado, $Tipo, $Puntaje]);
            $pregunta_id = $conexion->lastInsertId();

            if ($Tipo === 'verdadero_falso') {
                $correcta = isset($_POST['respuesta_correcta_vf']) ? $_POST['respuesta_correcta_vf'] : 'Verdadero';
                $opciones = [
                    ['Verdadero', ($correcta === 'Verdadero') ? 1 : 0],
                    ['Falso', ($correcta === 'Falso') ? 1 : 0]
                ];
                foreach ($opciones as $opcion) {
                    $sql = "INSERT INTO Opciones (IDPregunta, TextoOpcion, es_correcta) VALUES (?, ?, ?)";
                    $stmt = $conexion->prepare($sql);
                    $stmt->execute([$pregunta_id, $opcion[0], $opcion[1]]);
                }
            }
            $sql_update = "UPDATE Cuestionarios SET CantidadPreguntas = (SELECT COUNT(*) FROM Preguntas WHERE IDCuestionario = ?) WHERE ID = ?";
            $stmt_update = $conexion->prepare($sql_update);
            $stmt_update->execute([$cuestionario_id, $cuestionario_id]);
            $mensaje = "Pregunta agregada correctamente.";
        }
    } elseif ($accion === 'eliminar_pregunta') {
        $pregunta_id = (int)$_POST['pregunta_id'];
        $stmt = $conexion->prepare("DELETE FROM Opciones WHERE IDPregunta = ?");
        $stmt->execute([$pregunta_id]);
        $stmt = $conexion->prepare("DELETE FROM Preguntas WHERE ID = ?");
        $stmt->execute([$pregunta_id]);
        $sql_update = "UPDATE Cuestionarios SET CantidadPreguntas = (SELECT COUNT(*) FROM Preguntas WHERE IDCuestionario = ?) WHERE ID = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->execute([$cuestionario_id, $cuestionario_id]);
        $mensaje = "Pregunta eliminada.";
    } elseif ($accion === 'agregar_opcion') {
        $pregunta_id = (int)$_POST['pregunta_id'];
        $TextoOpcion = trim($_POST['TextoOpcion'] ?? '');
        $es_correcta = isset($_POST['es_correcta']) ? 1 : 0;
        if (!empty($TextoOpcion)) {
            $sql = "INSERT INTO Opciones (IDPregunta, TextoOpcion, es_correcta) VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$pregunta_id, $TextoOpcion, $es_correcta]);
            $mensaje = "Opción agregada.";
        }
    } elseif ($accion === 'eliminar_opcion') {
        $opcion_id = (int)$_POST['opcion_id'];
        $stmt = $conexion->prepare("DELETE FROM Opciones WHERE ID = ?");
        $stmt->execute([$opcion_id]);
        $mensaje = "Opción eliminada.";
    }
    header("Location: gestionar_preguntas.php?cuestionario_id=$cuestionario_id&mensaje=" . urlencode($mensaje));
    exit;
}
$stmt = $conexion->prepare("SELECT * FROM Preguntas WHERE IDCuestionario = ? ORDER BY ID");
$stmt->execute([$cuestionario_id]);
$preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($preguntas as &$preg) {
    $stmt = $conexion->prepare("SELECT * FROM Opciones WHERE IDPregunta = ?");
    $stmt->execute([$preg['ID']]);
    $preg['Opciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($preg);
include("../../header.php");
?>
<div class="container mt-4">
    <h2>Gestionar Preguntas - <?= htmlspecialchars($cuestionario['Titulo']) ?></h2>
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <div class="mb-3">
        <a href="editar_cuestionario.php?cuestionario_id=<?= $cuestionario_id ?>" class="btn btn-warning">Editar Cuestionario</a>
        <a href="../../Cursos_Usuario/contenido.php?id=<?= $cuestionario['IDCurso'] ?>" class="btn btn-secondary">Volver al Curso</a>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Nueva Pregunta</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="accion" value="agregar_pregunta">
                <div class="mb-3">
                    <label class="form-label">Enunciado</label>
                    <textarea class="form-control" name="Enunciado" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="Tipo">
                            <option value="opcion_unica">Opción Única</option>
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="verdadero_falso">Verdadero/Falso</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Puntaje</label>
                        <input type="number" step="0.01" class="form-control" name="Puntaje" value="1.00" min="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success">Agregar Pregunta</button>
                    </div>
                </div>
                <div id="campo_verdadero_falso" style="display:none; margin-top:15px;">
                    <label class="form-label fw-bold">Respuesta correcta:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="respuesta_correcta_vf" value="Verdadero" id="vf_verdadero" checked>
                        <label class="form-check-label" for="vf_verdadero">Verdadero</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="respuesta_correcta_vf" value="Falso" id="vf_falso">
                        <label class="form-check-label" for="vf_falso">Falso</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h4>Preguntas del Cuestionario</h4>
    <?php if (empty($preguntas)): ?>
        <div class="alert alert-info">No hay preguntas aún. Agrega la primera.</div>
    <?php else: ?>
        <?php foreach ($preguntas as $pregunta): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><strong>Pregunta ID <?= $pregunta['ID'] ?></strong> (<?= $pregunta['Tipo'] ?>) - Puntaje: <?= $pregunta['Puntaje'] ?></span>
                    <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar_pregunta">
                            <input type="hidden" name="pregunta_id" value="<?= $pregunta['ID'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta pregunta y sus opciones?')">Eliminar</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($pregunta['Enunciado'])) ?></p>
                    <?php if ($pregunta['Tipo'] !== 'verdadero_falso'): ?>
                        <h6>Opciones</h6>
                        <ul>
                            <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                                <li>
                                    <?= htmlspecialchars($opcion['TextoOpcion']) ?>
                                    <?php if ($opcion['es_correcta']): ?>
                                        <span class="badge bg-success">Correcta</span>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="accion" value="eliminar_opcion">
                                        <input type="hidden" name="opcion_id" value="<?= $opcion['ID'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar opción?')">x</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="accion" value="agregar_opcion">
                            <input type="hidden" name="pregunta_id" value="<?= $pregunta['ID'] ?>">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="TextoOpcion" placeholder="Texto de la opción" required>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="es_correcta" id="correcta_<?= $pregunta['ID'] ?>">
                                        <label class="form-check-label" for="correcta_<?= $pregunta['ID'] ?>">Correcta</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary">Agregar Opción</button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <?php
                        $correcta = '';
                        foreach ($pregunta['Opciones'] as $op) {
                            if ($op['es_correcta'] == 1) {
                                $correcta = $op['TextoOpcion'];
                                break;
                            }
                        }
                        ?>
                        <p><strong>Respuesta correcta:</strong> <?= $correcta ?: 'No definida' ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.querySelector('select[name="Tipo"]');
    const campoVF = document.getElementById('campo_verdadero_falso');

    function toggleCampoVF() {
        if (tipoSelect.value === 'verdadero_falso') {
            campoVF.style.display = 'block';
        } else {
            campoVF.style.display = 'none';
        }
    }

    tipoSelect.addEventListener('change', toggleCampoVF);
    toggleCampoVF();
});
</script>

<?php include("../../footer.php"); ?>