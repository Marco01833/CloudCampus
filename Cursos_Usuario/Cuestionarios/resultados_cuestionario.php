<?php
include("../../autenticacion.php");
include("../../bd.php");

$intento_id = isset($_GET['intento_id']) ? (int)$_GET['intento_id'] : 0;
if ($intento_id <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Intento no válido");
    exit;
}

$stmt = $conexion->prepare("SELECT i.*, c.Titulo as CuestionarioTitulo, c.IDCurso 
                            FROM IntentosCuestionario i
                            INNER JOIN Cuestionarios c ON i.IDCuestionario = c.ID
                            WHERE i.ID = ? AND i.IDUsuario = ?");
$stmt->execute([$intento_id, $_SESSION['user_id']]);
$intento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$intento) {
    die("Intento no encontrado o no autorizado");
}

$stmt = $conexion->prepare("SELECT r.*, p.Enunciado, o.TextoOpcion 
                            FROM RespuestasUsuario r
                            INNER JOIN Preguntas p ON r.IDPregunta = p.ID
                            LEFT JOIN Opciones o ON r.IDOpcionSeleccionada = o.ID
                            WHERE r.IDIntento = ?");
$stmt->execute([$intento_id]);
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../../header.php");
?>
<div class="container mt-4">
    <h2>Resultados del Cuestionario</h2>
    <p><strong>Cuestionario:</strong> <?= htmlspecialchars($intento['CuestionarioTitulo']) ?></p>
    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($intento['FechaInicio'])) ?></p>
    <p><strong>Calificación:</strong> <?= number_format($intento['Calificacion'], 2) ?>%</p>
    <p><strong>Aciertos:</strong> <?= $intento['Aciertos'] ?> | <strong>Fallos:</strong> <?= $intento['Fallos'] ?></p>

    <?php if (!empty($respuestas)): ?>
        <h5>Detalle de respuestas</h5>
        <ul>
            <?php foreach ($respuestas as $resp): ?>
                <li>
                    <strong><?= htmlspecialchars($resp['Enunciado']) ?></strong><br>
                    Respuesta: <?= htmlspecialchars($resp['TextoOpcion'] ?? 'No respondida') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="mt-3">
        <a href="cuestionario.php?cuestionario_id=<?= $intento['IDCuestionario'] ?>" class="btn btn-primary">Volver al Cuestionario</a>
        <a href="../../Cursos_Usuario/contenido.php?id=<?= $intento['IDCurso'] ?>" class="btn btn-secondary">Volver al Curso</a>
    </div>
</div>
<?php include("../../footer.php"); ?>