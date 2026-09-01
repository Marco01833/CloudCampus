<?php
include("../../autenticacion.php");
include("../../bd.php");
$cuestionario_id = isset($_GET['cuestionario_id']) ? (int)$_GET['cuestionario_id'] : 0;
if ($cuestionario_id <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Cuestionario no válido");
    exit;
}
$stmt = $conexion->prepare("SELECT c.*, co.Titulo as ContenidoTitulo, cu.Nombre as CursoNombre, cu.ID as CursoID
                            FROM Cuestionarios c
                            INNER JOIN Contenido co ON c.IDContenido = co.ID
                            INNER JOIN cursos cu ON c.IDCurso = cu.ID
                            WHERE c.ID = ?");
$stmt->execute([$cuestionario_id]);
$cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cuestionario) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Cuestionario no encontrado");
    exit;
}
$stmt_preguntas = $conexion->prepare("SELECT COUNT(*) FROM Preguntas WHERE IDCuestionario = ?");
$stmt_preguntas->execute([$cuestionario_id]);
$total_preguntas = $stmt_preguntas->fetchColumn();

$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($cuestionario['IDCreador'] == $_SESSION['user_id']);
$esEstudiante = ($rol_usuario == 1);

if ($esEstudiante) {
    $stmt = $conexion->prepare("SELECT 1 FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ? AND Estado = 1");
    $stmt->execute([$_SESSION['user_id'], $cuestionario['CursoID']]);
    if (!$stmt->fetch()) {
        die("No estás inscrito en este curso.");
    }
}

$intentos = [];
$intento_activo = null;
if ($esEstudiante) {
    $stmt = $conexion->prepare("SELECT * FROM IntentosCuestionario WHERE IDUsuario = ? AND IDCuestionario = ? ORDER BY ID DESC");
    $stmt->execute([$_SESSION['user_id'], $cuestionario_id]);
    $intentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($intentos as $int) {
        if ($int['Estado'] === 'en_progreso') {
            $intento_activo = $int;
            break;
        }
    }
}

include("../../header.php");
?>
<div class="container mt-4">
    <h2><?= htmlspecialchars($cuestionario['Titulo']) ?></h2>
    <p><strong>Curso:</strong> <?= htmlspecialchars($cuestionario['CursoNombre']) ?></p>
    <p><strong>Contenido:</strong> <?= htmlspecialchars($cuestionario['ContenidoTitulo']) ?></p>
    <p><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($cuestionario['Descripcion'] ?? '')) ?></p>
    <p><strong>Tiempo Límite:</strong> <?= $cuestionario['TiempoLimite'] ? $cuestionario['TiempoLimite'] . ' minutos' : 'Sin límite' ?></p>

    <?php if ($esAdmin || $esProfesor): ?>
        <div class="mb-3">
            <a href="../../Cursos_Usuario/contenido.php?id=<?= $cuestionario['CursoID'] ?>" class="btn btn-secondary">Volver al Curso</a>
            
        </div>
    <?php elseif ($esEstudiante): ?>
        <div class="mb-3">
            <a href="../../Cursos_Usuario/contenido.php?id=<?= $cuestionario['CursoID'] ?>" class="btn btn-secondary">Volver al Curso</a>
        </div>
    <?php endif; ?>

    <?php if ($esEstudiante): ?>
        <?php if ($intento_activo): ?>
            <div class="alert alert-warning">
                Tienes un intento en progreso. <a href="responder_cuestionario.php?intento_id=<?= $intento_activo['ID'] ?>" class="btn btn-primary">Continuar</a>
            </div>
        <?php else: ?>
            <?php if (!empty($intentos)): ?>
                <h5>Intentos anteriores</h5>
                <table class="table">
                    <thead>
                        <tr><th>Fecha</th><th>Calificación</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($intentos as $int): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($int['FechaInicio'])) ?></td>
                                <td><?= $int['Calificacion'] !== null ? number_format($int['Calificacion'], 2) . '%' : '-' ?></td>
                                <td>
                                    <?php if ($int['Estado'] === 'finalizado'): ?>
                                        <a href="resultados_cuestionario.php?intento_id=<?= $int['ID'] ?>" class="btn btn-sm btn-info">Ver Resultados</a>
                                    <?php else: ?>
                                        <?= $int['Estado'] ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php endif; ?>
            <form method="post" action="responder_cuestionario.php">
                <input type="hidden" name="cuestionario_id" value="<?= $cuestionario_id ?>">
                <button type="submit" class="btn btn-success" <?= ($total_preguntas == 0) ? 'disabled' : '' ?>>
                    <?= empty($intentos) ? 'Comenzar Cuestionario' : 'Nuevo Intento' ?>
                </button>
                <a href="../../Cursos_Usuario/contenido.php?id=<?= $cuestionario['CursoID'] ?>" class="btn btn-secondary">Volver al Curso</a>
            </form>
            <?php if ($total_preguntas == 0): ?>
                <p class="text-muted">No hay preguntas disponibles. El profesor aún no ha agregado preguntas.</p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include("../../footer.php"); ?>