<?php
include("../autenticacion.php");
include("../bd.php");

if ($_SESSION['rol'] != 2) {
    header("Location: ../dashboard.php");
    exit;
}
$totalCursos = $conexion->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$totalPendientes = $conexion->query("SELECT COUNT(*) FROM cursos WHERE Estado = 'Pendiente'")->fetchColumn();
$totalAprobados = $conexion->query("SELECT COUNT(*) FROM cursos WHERE Estado = 'Aprobado'")->fetchColumn();
$totalRechazados = $conexion->query("SELECT COUNT(*) FROM cursos WHERE Estado = 'Rechazado'")->fetchColumn();
$totalEstudiantes = $conexion->query("SELECT COUNT(*) FROM Usuarios WHERE IDRol = 1")->fetchColumn();
$estudiantesPorCurso = $conexion->query("
    SELECT c.Nombre, COUNT(i.IDUsuario) as total_inscritos
    FROM cursos c
    LEFT JOIN Inscripciones i ON c.ID = i.IDCurso AND i.Estado = 1
    WHERE c.Estado = 'Aprobado'
    GROUP BY c.ID
    ORDER BY total_inscritos DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$profesoresCursos = $conexion->query("
    SELECT u.Correo, COUNT(c.ID) as total_cursos
    FROM Usuarios u
    LEFT JOIN cursos c ON u.ID = c.IDUsuario
    WHERE u.IDRol = 3
    GROUP BY u.ID
    ORDER BY total_cursos DESC
")->fetchAll(PDO::FETCH_ASSOC);
$page = isset($_GET['page']) ? $_GET['page'] : 'resumen';
$allowed = ['resumen', 'estudiantes', 'profesores'];
if (!in_array($page, $allowed)) $page = 'resumen';

include("../header.php");
?>

<div class="container mt-4">
    <h1 class="mb-4"> Reportes de Administración</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $page == 'resumen' ? 'active' : '' ?>" href="?page=resumen"> Resumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page == 'estudiantes' ? 'active' : '' ?>" href="?page=estudiantes"> Top Estudiantes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page == 'profesores' ? 'active' : '' ?>" href="?page=profesores"> Profesores</a>
        </li>
    </ul>

    <div class="row">
        <div class="col-12">
            <?php
            switch ($page) {
                case 'resumen':?>
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h6 class="card-title">Total Cursos</h6>
                                    <p class="card-text display-6"><?= $totalCursos ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h6 class="card-title">Pendientes</h6>
                                    <p class="card-text display-6"><?= $totalPendientes ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h6 class="card-title">Aprobados</h6>
                                    <p class="card-text display-6"><?= $totalAprobados ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-danger">
                                <div class="card-body">
                                    <h6 class="card-title">Rechazados</h6>
                                    <p class="card-text display-6"><?= $totalRechazados ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h6 class="card-title">Estudiantes</h6>
                                    <p class="card-text display-6"><?= $totalEstudiantes ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Distribución de Cursos por Estado</div>
                                <div class="card-body">
                                    <canvas id="estadoChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">Top 5 Cursos con más Estudiantes</div>
                                <div class="card-body">
                                    <?php if (count($estudiantesPorCurso) > 0): ?>
                                        <table class="table table-sm">
                                            <thead><tr><th>Curso</th><th>Inscritos</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($estudiantesPorCurso as $row): ?>
                                                    <tr><td><?= htmlspecialchars($row['Nombre']) ?></td><td><?= $row['total_inscritos'] ?></td></tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p class="text-muted">No hay cursos aprobados con inscripciones.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">Profesores y sus Cursos</div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <thead><tr><th>Profesor</th><th>Cursos creados</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($profesoresCursos as $row): ?>
                                                <tr><td><?= htmlspecialchars($row['Correo']) ?></td><td><?= $row['total_cursos'] ?></td></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            new Chart(document.getElementById('estadoChart').getContext('2d'), {
                                type: 'pie',
                                data: {
                                    labels: ['Pendiente', 'Aprobado', 'Rechazado'],
                                    datasets: [{
                                        data: [<?= $totalPendientes ?>, <?= $totalAprobados ?>, <?= $totalRechazados ?>],
                                        backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                                    }]
                                },
                                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                            });
                        });
                    </script>
                    <?php
                    break;

                case 'estudiantes':
                    include('estudiantes_por_curso.php');
                    break;
                case 'profesores':
                    include('profesores_cursos.php');
                    break;
            }
            ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include("../footer.php"); ?>