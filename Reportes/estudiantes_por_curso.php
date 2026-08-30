<?php
$stmt = $conexion->prepare("
    SELECT c.Nombre as curso, COUNT(i.IDUsuario) as total_inscritos
    FROM cursos c
    LEFT JOIN Inscripciones i ON c.ID = i.IDCurso AND i.Estado = 1
    WHERE c.Estado = 'Aprobado'
    GROUP BY c.ID
    ORDER BY total_inscritos DESC
    LIMIT 10
");
$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="card">
    <div class="card-header">Cursos con más estudiantes inscritos</div>
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>Curso</th><th>Inscritos</th></tr></thead>
            <tbody>
                <?php if (count($cursos) > 0): ?>
                    <?php foreach ($cursos as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['curso']) ?></td>
                            <td><span class="badge bg-primary"><?= $row['total_inscritos'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" class="text-muted">No hay cursos aprobados con inscripciones.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>