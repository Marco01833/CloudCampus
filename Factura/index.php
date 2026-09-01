<?php
include("../autenticacion.php");
include("../bd.php");
include("../header.php");
$user_id = $_SESSION['user_id'];
$stmt = $conexion->prepare("
    SELECT ID, Fecha, Total, Estado, MetodoPago 
    FROM Facturas 
    WHERE IDUsuario = ? 
    ORDER BY Fecha DESC
");
$stmt->execute([$user_id]);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h1 class="mb-4">📄 Mis Facturas</h1>

    <?php if (empty($facturas)): ?>
        <div class="alert alert-info">
            No tienes facturas registradas. 
            <a href="../Productos/index.php" class="alert-link">Explora nuestros cursos</a>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Método de pago</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($factura['Fecha'])) ?></td>
                        <td><span class="fw-bold">$<?= number_format($factura['Total'], 2) ?></span></td>
                        <td><?= htmlspecialchars($factura['MetodoPago'] ?? 'No especificado') ?></td>
                        <td>
                            <span class="badge <?= $factura['Estado'] ? 'bg-success' : 'bg-warning' ?>">
                                <?= $factura['Estado'] ? 'Pagada' : 'Pendiente' ?>
                            </span>
                        </td>
                        <td>
                            <a href="factura.php?id=<?= $factura['ID'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> Ver detalle
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include("../footer.php"); ?>