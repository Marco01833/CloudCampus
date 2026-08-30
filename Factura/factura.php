<?php
include("../autenticacion.php");
include("../bd.php");

$id_factura = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$error = null;
$factura = null;
$detalles = [];
$nombre_completo = '';

if ($id_factura <= 0) {
    $error = "No se especificó una factura válida.";
} else {
    try {
        $stmt = $conexion->prepare("
            SELECT ID, Fecha, Total, MetodoPago, Estado
            FROM Facturas
            WHERE ID = ? AND IDUsuario = ?
        ");
        $stmt->execute([$id_factura, $user_id]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$factura) {
            $error = "Factura no encontrada o no te pertenece.";
        } else {
            $stmt = $conexion->prepare("SELECT Nombre, Apellidos FROM DatosPersonales WHERE IDUsuario = ?");
            $stmt->execute([$user_id]);
            $datos_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_completo = trim(($datos_usuario['Nombre'] ?? '') . ' ' . ($datos_usuario['Apellidos'] ?? ''));
                        if (empty($nombre_completo)) {
                $nombre_completo = 'Usuario';
            }
            $stmt = $conexion->prepare("
                SELECT 
                    df.TipoCompra,
                    df.IDReferencia,
                    df.PrecioUnidad,
                    df.Descuento,
                    CASE 
                        WHEN df.TipoCompra = 'curso' THEN c.Nombre
                        WHEN df.TipoCompra = 'plan' THEN p.Nombre
                        ELSE 'Desconocido'
                    END AS NombreItem,
                    CASE 
                        WHEN df.TipoCompra = 'curso' THEN 'Curso'
                        WHEN df.TipoCompra = 'plan' THEN 'Plan'
                        ELSE 'Otro'
                    END AS TipoItem
                FROM DetalleFactura df
                LEFT JOIN cursos c ON df.TipoCompra = 'curso' AND df.IDReferencia = c.ID
                LEFT JOIN Planes p ON df.TipoCompra = 'plan' AND df.IDReferencia = p.ID
                WHERE df.IDFactura = ?
            ");
            $stmt->execute([$id_factura]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Error al obtener la factura: " . $e->getMessage();
    }
}

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4">🧾 Detalle de Factura</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <a href="index.php" class="btn btn-secondary">Volver al listado</a>
    <?php elseif ($factura): ?>
        <div class="card mb-4 shadow-sm" id="facturaCard">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Factura #<?= $factura['ID'] ?></h5>
                <span class="badge <?= $factura['Estado'] ? 'bg-success' : 'bg-warning' ?>">
                    <?= $factura['Estado'] ? 'Pagada' : 'Pendiente' ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>👤 Cliente:</strong> <?= htmlspecialchars($nombre_completo) ?></p>
                        <p><strong>📅 Fecha:</strong> <?= date('d/m/Y H:i', strtotime($factura['Fecha'])) ?></p>
                        <p><strong>💳 Método de pago:</strong> <?= htmlspecialchars($factura['MetodoPago'] ?? 'No especificado') ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>💰 Total:</strong> <span class="fs-4 fw-bold text-primary">$<?= number_format($factura['Total'], 2) ?></span></p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th class="text-end">Precio unidad</th>
                                <th class="text-end">Descuento</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal_total = 0;
                            $descuento_total = 0;
                            foreach ($detalles as $item): 
                                $subtotal_item = $item['PrecioUnidad'] - ($item['Descuento'] ?? 0);
                                $subtotal_total += $subtotal_item;
                                $descuento_total += ($item['Descuento'] ?? 0);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item['TipoItem']) ?></td>
                                <td><?= htmlspecialchars($item['NombreItem'] ?? 'Ítem eliminado') ?></td>
                                <td class="text-end">$<?= number_format($item['PrecioUnidad'], 2) ?></td>
                                <td class="text-end text-danger">- $<?= number_format($item['Descuento'] ?? 0, 2) ?></td>
                                <td class="text-end fw-bold">$<?= number_format($subtotal_item, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                <td class="text-end">$<?= number_format($subtotal_total + $descuento_total, 2) ?></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Descuentos</strong></td>
                                <td class="text-end text-danger">- $<?= number_format($descuento_total, 2) ?></td>
                                <td></td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="3" class="text-end"><strong>Total</strong></td>
                                <td colspan="2" class="text-end fw-bold fs-5">$<?= number_format($factura['Total'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver al listado
                    </a>
                    <button id="btnDescargarPDF" class="btn btn-danger">
                        <i class="bi bi-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    document.getElementById('btnDescargarPDF').addEventListener('click', function() {
        const elemento = document.querySelector('#facturaCard');
        if (!elemento) return;

        const opt = {
            margin: 0.5,
            filename: 'factura_<?= $factura['ID'] ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(elemento).save();
    });
</script>

<?php include("../footer.php"); ?>