<?php
include("../../autenticacion.php");
include("../../bd.php");

$curso_id = isset($_GET['id_curso']) ? (int)$_GET['id_curso'] : 0;
if ($curso_id <= 0) {
    header("Location: ../../Cursos_Usuario/contenido.php?mensaje=Curso no válido");
    exit;
}
$stmt = $conexion->prepare("SELECT IDUsuario FROM cursos WHERE ID = ?");
$stmt->execute([$curso_id]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($curso && $curso['IDUsuario'] == $_SESSION['user_id']);
if (!$esAdmin && !$esProfesor) {
    die("No tienes permiso.");
}
$stmt = $conexion->prepare("SELECT u.ID, u.Correo, dp.Nombre, dp.Apellidos 
                            FROM Inscripciones i
                            INNER JOIN Usuarios u ON i.IDUsuario = u.ID
                            LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
                            WHERE i.IDCurso = ? AND i.Estado = 1");
$stmt->execute([$curso_id]);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conexion->prepare("SELECT ID, Titulo FROM Cuestionarios WHERE IDCurso = ? ORDER BY ID");
$stmt->execute([$curso_id]);
$cuestionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../../header.php");
?>
<div class="container mt-4">
    <h2>Calificaciones de Estudiantes</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Estudiante</th>
                <?php foreach ($cuestionarios as $c): ?>
                    <th><?= htmlspecialchars($c['Titulo']) ?></th>
                <?php endforeach; ?>
                <th>Promedio</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $est): ?>
                <tr>
                    <td><?= htmlspecialchars(($est['Nombre'] ?? '') . ' ' . ($est['Apellidos'] ?? '')) ?></td>
                    <?php
                    $calificaciones = [];
                    foreach ($cuestionarios as $c):
                        $stmt = $conexion->prepare("SELECT Calificacion FROM IntentosCuestionario 
                                                    WHERE IDUsuario = ? AND IDCuestionario = ? AND Estado = 'finalizado'
                                                    ORDER BY ID DESC LIMIT 1");
                        $stmt->execute([$est['ID'], $c['ID']]);
                        $nota = $stmt->fetchColumn();
                        $calificaciones[] = $nota !== false ? $nota : null;
                    ?>
                        <td><?= $nota !== false ? number_format($nota, 2) : '-' ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php
                        $validas = array_filter($calificaciones, function($v) { return $v !== null; });
                        $promedio = !empty($validas) ? array_sum($validas) / count($validas) : 0;
                        echo number_format($promedio, 2);
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="../../Cursos_Usuario/contenido.php?id=<?= $curso_id ?>" class="btn btn-secondary">Volver al Curso</a>
</div>
<?php include("../../footer.php"); ?>