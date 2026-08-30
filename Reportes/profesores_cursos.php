<?php
$stmt = $conexion->prepare("
    SELECT u.Correo as profesor, COUNT(c.ID) as total_cursos,
           SUM(CASE WHEN c.Estado = 'Aprobado' THEN 1 ELSE 0 END) as aprobados,
           SUM(CASE WHEN c.Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
           SUM(CASE WHEN c.Estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazados
    FROM Usuarios u
    LEFT JOIN cursos c ON u.ID = c.IDUsuario
    WHERE u.IDRol = 3
    GROUP BY u.ID
    ORDER BY total_cursos DESC
");
$stmt->execute();
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header">Profesores y estado de sus cursos</div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr><th>Profesor</th><th>Total cursos</th><th>Aprobados</th><th>Pendientes</th><th>Rechazados</th></tr>
            </thead>
            <tbody>
                <?php if (count($profesores) > 0): ?>
                    <?php foreach ($profesores as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['profesor']) ?></td>
                            <td><span class="badge bg-secondary"><?= $p['total_cursos'] ?></span></td>
                            <td><span class="badge bg-success"><?= $p['aprobados'] ?></span></td>
                            <td><span class="badge bg-warning"><?= $p['pendientes'] ?></span></td>
                            <td><span class="badge bg-danger"><?= $p['rechazados'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted">No hay profesores registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>