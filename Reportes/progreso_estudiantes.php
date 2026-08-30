<?php
$stmt = $conexion->prepare("
    SELECT u.Correo, c.Nombre as curso, 
           COUNT(p.ID) as lecciones_completadas,
           (SELECT COUNT(*) FROM Contenido WHERE IDCurso = c.ID) as total_lecciones,
           ROUND(COUNT(p.ID) * 100.0 / (SELECT COUNT(*) FROM Contenido WHERE IDCurso = c.ID), 2) as porcentaje
    FROM Usuarios u
    JOIN progreso_estudiante p ON u.ID = p.IDUsuario
    JOIN cursos c ON p.IDCurso = c.ID
    WHERE p.completado = 1
    GROUP BY u.ID, c.ID
    ORDER BY porcentaje DESC
    LIMIT 20
");
$stmt->execute();
$progreso = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header">Progreso de estudiantes (top 20)</div>
    <div class="card-body">
        <?php if (count($progreso) > 0): ?>
            <table class="table table-hover">
                <thead>
                    <tr><th>Estudiante</th><th>Curso</th><th>Lecciones completadas</th><th>Total</th><th>Progreso</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($progreso as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Correo']) ?></td>
                            <td><?= htmlspecialchars($row['curso']) ?></td>
                            <td><?= $row['lecciones_completadas'] ?></td>
                            <td><?= $row['total_lecciones'] ?></td>
                            <td>
                                <div class="progress" style="height:20px;">
                                    <div class="progress-bar progress-bar-striped bg-info" 
                                         style="width: <?= $row['porcentaje'] ?>%;">
                                        <?= $row['porcentaje'] ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No hay registros de progreso de estudiantes.</p>
        <?php endif; ?>
    </div>
</div>