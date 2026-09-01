<?php
include("../../autenticacion.php");
include("../../bd.php");

define('NOTA_MINIMA', 70); 

$curso_id = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : 0;
if ($curso_id <= 0) {
    header("Location: ../../Cursos_Usuario/index.php?mensaje=Curso no válido");
    exit;
}

$stmt = $conexion->prepare("SELECT 1 FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ? AND Estado = 1");
$stmt->execute([$_SESSION['user_id'], $curso_id]);
if (!$stmt->fetch()) {
    die("No estás inscrito en este curso.");
}

$stmt = $conexion->prepare("SELECT c.*, 
                            (SELECT Calificacion FROM IntentosCuestionario 
                             WHERE IDCuestionario = c.ID AND IDUsuario = ? AND Estado = 'finalizado'
                             ORDER BY ID DESC LIMIT 1) as Calificacion
                            FROM Cuestionarios c
                            WHERE c.IDCurso = ?
                            ORDER BY c.ID");
$stmt->execute([$_SESSION['user_id'], $curso_id]);
$cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calificaciones = array_filter(array_column($cuestionarios, 'Calificacion'), function($v) { return $v !== null; });
$promedio = !empty($calificaciones) ? array_sum($calificaciones) / count($calificaciones) : 0;

$aprobado = $promedio >= NOTA_MINIMA;

include("../../header.php");
?>
<div class="container mt-4">
    <h2>Mis Calificaciones del Curso</h2>

    <h4>Promedio General: <?= number_format($promedio, 2) ?>%</h4>
    <p>Nota Mínima para Aprobación: <?= NOTA_MINIMA ?>%</p>
    <div class="alert <?= $aprobado ? 'alert-success' : 'alert-danger' ?>">
        <strong><?= $aprobado ? '¡Aprobado!' : 'Reprobado' ?></strong>
        <?php if ($aprobado): ?>
            <a href="../../Certificados/generar.php?curso_id=<?= $curso_id ?>" class="btn btn-success btn-sm">Generar Certificado</a>
        <?php endif; ?>
    </div>

    <h5>Calificaciones por Cuestionario</h5>
    <table class="table table-bordered">
        <thead>
            <tr><th>Cuestionario</th><th>Calificación (%)</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($cuestionarios as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['Titulo']) ?></td>
                    <td><?= $c['Calificacion'] !== null ? number_format($c['Calificacion'], 2) : '-' ?></td>
                    <td>
                        <?php if ($c['Calificacion'] !== null): ?>
                            <?= $c['Calificacion'] >= NOTA_MINIMA ? 'Aprobado' : 'Reprobado' ?>
                        <?php else: ?>
                            No intentado
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-3">
        <a href="../../Cursos_Usuario/contenido.php?id=<?= $curso_id ?>" class="btn btn-secondary">Volver al Curso</a>
    </div>
</div>
<?php include("../../footer.php"); ?>