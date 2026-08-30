<?php
include("../../autenticacion.php");
include("../../bd.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar'])) {
    $intento_id_post = isset($_POST['intento_id']) ? (int)$_POST['intento_id'] : 0;
    if ($intento_id_post <= 0) {
        die("Intento no válido");
    }
    
    $stmt = $conexion->prepare("SELECT * FROM IntentosCuestionario WHERE ID = ? AND IDUsuario = ? AND Estado = 'en_progreso'");
    $stmt->execute([$intento_id_post, $_SESSION['user_id']]);
    $intento_actual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$intento_actual) {
        die("Intento no válido o ya finalizado");
    }
    
    $intento_id = $intento_actual['ID'];
    $cuestionario_id = $intento_actual['IDCuestionario'];
    
    $stmt_curso = $conexion->prepare("SELECT IDCurso FROM Cuestionarios WHERE ID = ?");
    $stmt_curso->execute([$cuestionario_id]);
    $curso_data = $stmt_curso->fetch(PDO::FETCH_ASSOC);
    $id_curso = $curso_data['IDCurso'] ?? 0;
    error_log("ID curso obtenido desde cuestionario: $id_curso");
    
    $stmt = $conexion->prepare("SELECT * FROM Preguntas WHERE IDCuestionario = ? ORDER BY ID");
    $stmt->execute([$cuestionario_id]);
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($preguntas as &$preg) {
        $stmt = $conexion->prepare("SELECT * FROM Opciones WHERE IDPregunta = ?");
        $stmt->execute([$preg['ID']]);
        $preg['Opciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($preg);
    
    $respuestas = $_POST['respuestas'] ?? [];
    $puntaje_total = 0;
    $aciertos = 0;
    $fallos = 0;
    
    foreach ($preguntas as $pregunta) {
        $puntaje_pregunta = $pregunta['Puntaje'];
        $correctas = array_filter($pregunta['Opciones'], function($op) { return $op['es_correcta'] == 1; });
        $correctas_ids = array_column($correctas, 'ID');
        $seleccionadas = isset($respuestas[$pregunta['ID']]) ? $respuestas[$pregunta['ID']] : [];
        if (!is_array($seleccionadas)) $seleccionadas = [$seleccionadas];
        $seleccionadas = array_map('intval', $seleccionadas);
        
        $es_correcta = false;
        if ($pregunta['Tipo'] === 'opcion_unica' || $pregunta['Tipo'] === 'verdadero_falso') {
            $es_correcta = (count($seleccionadas) === 1 && in_array($correctas_ids[0], $seleccionadas));
        } elseif ($pregunta['Tipo'] === 'opcion_multiple') {
            $seleccionadas_correctas = array_intersect($seleccionadas, $correctas_ids);
            $incorrectas = array_diff($seleccionadas, $correctas_ids);
            $es_correcta = (count($seleccionadas_correctas) === count($correctas_ids) && empty($incorrectas));
        }
        
        if ($es_correcta) {
            $puntaje_total += $puntaje_pregunta;
            $aciertos++;
        } else {
            $fallos++;
        }
    }
    
    $puntaje_maximo = array_sum(array_column($preguntas, 'Puntaje'));
    $calificacion = ($puntaje_maximo > 0) ? ($puntaje_total / $puntaje_maximo) * 100 : 0;
    
    $sql = "UPDATE IntentosCuestionario SET 
            FechaFin = NOW(), 
            Calificacion = ?, 
            Aciertos = ?, 
            Fallos = ?, 
            Estado = 'finalizado' 
            WHERE ID = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$calificacion, $aciertos, $fallos, $intento_id]);
    error_log("Intento actualizado: ID=$intento_id, calificacion=$calificacion");
    
    if ($id_curso > 0) {
        require_once __DIR__ . '/../../funciones_progreso.php';
        $progreso = actualizarProgresoCurso($conexion, $_SESSION['user_id'], $id_curso);
        error_log("Progreso final después de actualizar: $progreso");
    } else {
        error_log("ID de curso no válido ($id_curso) – no se actualiza progreso");
    }
    
    header("Location: resultados_cuestionario.php?intento_id=$intento_id");
    exit;
}

$intento_id = isset($_GET['intento_id']) ? (int)$_GET['intento_id'] : 0;
$cuestionario_id = isset($_POST['cuestionario_id']) ? (int)$_POST['cuestionario_id'] : (isset($_GET['cuestionario_id']) ? (int)$_GET['cuestionario_id'] : 0);

if ($intento_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM IntentosCuestionario WHERE ID = ? AND IDUsuario = ?");
    $stmt->execute([$intento_id, $_SESSION['user_id']]);
    $intento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$intento || $intento['Estado'] !== 'en_progreso') {
        header("Location: cuestionario.php?cuestionario_id=" . $intento['IDCuestionario'] . "&mensaje=Intento no válido");
        exit;
    }
    $cuestionario_id = $intento['IDCuestionario'];
} else {
    $stmt = $conexion->prepare("SELECT * FROM Cuestionarios WHERE ID = ?");
    $stmt->execute([$cuestionario_id]);
    $cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cuestionario) {
        die("Cuestionario no encontrado");
    }
    $stmt = $conexion->prepare("SELECT 1 FROM IntentosCuestionario WHERE IDUsuario = ? AND IDCuestionario = ? AND Estado = 'en_progreso'");
    $stmt->execute([$_SESSION['user_id'], $cuestionario_id]);
    if ($stmt->fetch()) {
        header("Location: cuestionario.php?cuestionario_id=$cuestionario_id&mensaje=Ya tienes un intento en progreso");
        exit;
    }
    $sql = "INSERT INTO IntentosCuestionario (IDUsuario, IDCuestionario, FechaInicio, Estado) VALUES (?, ?, NOW(), 'en_progreso')";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $cuestionario_id]);
    $intento_id = $conexion->lastInsertId();
    $intento = ['ID' => $intento_id, 'IDCuestionario' => $cuestionario_id, 'Estado' => 'en_progreso'];
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

if (empty($preguntas)) {
    die("Este cuestionario no tiene preguntas.");
}

include("../../header.php");
?>
<div class="container mt-4">
    <h2>Responder Cuestionario</h2>
    <form method="post">
        <input type="hidden" name="intento_id" value="<?= $intento_id ?>">
        <?php foreach ($preguntas as $index => $pregunta): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Pregunta <?= $index + 1 ?> (<?= $pregunta['Puntaje'] ?> pts)</h5>
                    <p><?= nl2br(htmlspecialchars($pregunta['Enunciado'])) ?></p>
                    <?php if ($pregunta['Tipo'] === 'verdadero_falso'): ?>
                        <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="respuestas[<?= $pregunta['ID'] ?>]" value="<?= $opcion['ID'] ?>" required>
                                <label class="form-check-label"><?= htmlspecialchars($opcion['TextoOpcion']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($pregunta['Tipo'] === 'opcion_unica'): ?>
                        <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="respuestas[<?= $pregunta['ID'] ?>]" value="<?= $opcion['ID'] ?>" required>
                                <label class="form-check-label"><?= htmlspecialchars($opcion['TextoOpcion']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($pregunta['Tipo'] === 'opcion_multiple'): ?>
                        <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="respuestas[<?= $pregunta['ID'] ?>][]" value="<?= $opcion['ID'] ?>">
                                <label class="form-check-label"><?= htmlspecialchars($opcion['TextoOpcion']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="finalizar" class="btn btn-success">Finalizar Cuestionario</button>
        <a href="cuestionario.php?cuestionario_id=<?= $cuestionario_id ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php include("../../footer.php"); ?>