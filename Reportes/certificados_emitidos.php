<?php
$stmt = $conexion->prepare("
    SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COUNT(*) as emitidos
    FROM certificados
    GROUP BY mes
    ORDER BY mes DESC
");
$stmt->execute();
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header">Certificados emitidos por mes</div>
    <div class="card-body">
        <canvas id="chartCertificados" height="150"></canvas>
        <table class="table table-sm mt-3">
            <thead><tr><th>Mes</th><th>Emitidos</th></tr></thead>
            <tbody>
                <?php foreach ($certificados as $row): ?>
                    <tr><td><?= htmlspecialchars($row['mes']) ?></td><td><?= $row['emitidos'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('chartCertificados').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($certificados as $row) echo '"' . $row['mes'] . '",'; ?>],
                datasets: [{
                    label: 'Certificados emitidos',
                    data: [<?php foreach ($certificados as $row) echo $row['emitidos'] . ','; ?>],
                    backgroundColor: '#17a2b8'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    });
</script>