<?php
include("../autenticacion.php");
include("../bd.php");

$sentencia = $conexion->prepare("SELECT * FROM Planes ORDER BY ID");
$sentencia->execute();
$lista_planes = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include("../header.php") ?>
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Gestión de Planes</h5>
            <a href="crear.php" class="btn btn-light btn-sm">Nuevo Plan</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Duración (días)</th>
                            <th>Descuento (%)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_planes)): ?>
                            <tr><td colspan="6" class="text-center">No hay planes registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_planes as $plan): ?>
                            <tr>
                                <td><?= $plan['ID'] ?></td>
                                <td><?= htmlspecialchars($plan['Nombre']) ?></td>
                                <td>$<?= number_format($plan['Precio'], 2) ?></td>
                                <td><?= $plan['DuracionDias'] ?? 'Indefinido' ?></td>
                                <td><?= number_format($plan['Descuento'] ?? 0, 2) ?>%</td>
                                <td>
                                    <a href="editar.php?txtID=<?= $plan['ID'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Total de planes: <?= count($lista_planes) ?>
        </div>
    </div>
</div>
<?php include("../footer.php"); ?>