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
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuestionario — Punto Código</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />

    <link rel="stylesheet" href="../../css/style.css">
</head>

<body>

    <main class="wrap quiz-page">

        <div class="quiz-header">
            <span class="eyebrow">// evaluación</span>
            <?php
            $stmt_titulo = $conexion->prepare("SELECT Titulo FROM Cuestionarios WHERE ID = ?");
            $stmt_titulo->execute([$cuestionario_id]);
            $cuestionario = $stmt_titulo->fetch(PDO::FETCH_ASSOC);
            $titulo = $cuestionario['Titulo'] ?? 'Cuestionario';
            ?>
            <h1>Cuestionario: <?= htmlspecialchars($titulo) ?></h1>
            <div class="quiz-progress-track">
                <?php
                $total_preguntas = count($preguntas);
                ?>
                <div class="quiz-progress-fill" style="width: <?= ($total_preguntas > 0) ? (1 / $total_preguntas * 100) : 0 ?>%;"></div>
            </div>
            <p class="quiz-progress-label">Pregunta 1 de <?= $total_preguntas ?></p>
        </div>

        <form id="quizForm" method="post">
            <input type="hidden" name="intento_id" value="<?= $intento_id ?>">

            <?php foreach ($preguntas as $index => $pregunta): ?>
                <div class="quiz-question">
                    <?php
                    $tag_class = '';
                    $tag_text = '';
                    if ($pregunta['Tipo'] === 'verdadero_falso') {
                        $tag_class = 'tag-vf';
                        $tag_text = 'Verdadero o falso';
                    } elseif ($pregunta['Tipo'] === 'opcion_unica') {
                        $tag_class = 'tag-unica';
                        $tag_text = 'Opción única';
                    } elseif ($pregunta['Tipo'] === 'opcion_multiple') {
                        $tag_class = 'tag-multiple';
                        $tag_text = 'Opción múltiple';
                    } else {
                        $tag_class = 'tag-unica';
                        $tag_text = 'Pregunta';
                    }
                    ?>
                    <span class="quiz-question-tag <?= $tag_class ?>"><?= $tag_text ?></span>
                    <p class="quiz-question-title"><?= nl2br(htmlspecialchars($pregunta['Enunciado'])) ?></p>

                    <?php if ($pregunta['Tipo'] === 'verdadero_falso' || $pregunta['Tipo'] === 'opcion_unica'): ?>
                        <div class="quiz-vf-options">
                            <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                                <label class="quiz-option">
                                    <input type="radio" name="respuestas[<?= $pregunta['ID'] ?>]" value="<?= $opcion['ID'] ?>" >
                                    <?= htmlspecialchars($opcion['TextoOpcion']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($pregunta['Tipo'] === 'opcion_multiple'): ?>
                        <div class="quiz-options">
                            <?php foreach ($pregunta['Opciones'] as $opcion): ?>
                                <label class="quiz-option">
                                    <input type="checkbox" name="respuestas[<?= $pregunta['ID'] ?>][]" value="<?= $opcion['ID'] ?>">
                                    <?= htmlspecialchars($opcion['TextoOpcion']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="finalizar" class="btn btn-primary">
                    Finalizar cuestionario <i class="fa-solid fa-arrow-right"></i>
                </button>
                <a href="cuestionario.php?cuestionario_id=<?= $cuestionario_id ?>" class="btn btn-secondary" style="background: var(--surface); border: 1px solid var(--border); color: var(--ink); padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none;">
                    Cancelar
                </a>
            </div>

        </form>

    </main>

</body>
</html>
<?php include("../../footer.php"); ?>