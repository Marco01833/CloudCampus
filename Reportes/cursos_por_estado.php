<?php
$stmt = $conexion->prepare("SELECT Estado, COUNT(*) as total FROM cursos GROUP BY Estado");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estados = ['Pendiente' => 0, 'Aprobado' => 0, 'Rechazado' => 0];
foreach ($data as $row) {
    $estados[$row['Estado']] = $row['total'];
}
?>
<div class="card">
    <div class="card-header">Cursos por estado</div>
    <div class="card-body">
        <canvas id="chartCursosEstado" height="200"></canvas>
        <table class="table table-sm mt-3">
            <tr><th>Estado</th><th>Cantidad</th></tr>
            <?php foreach ($estados as $estado => $total): ?>
                <tr><td><?= $estado ?></td><td><?= $total ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('chartCursosEstado').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Pendiente', 'Aprobado', 'Rechazado'],
                datasets: [{
                    data: [<?= $estados['Pendiente'] ?>, <?= $estados['Aprobado'] ?>, <?= $estados['Rechazado'] ?>],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    });
</script>