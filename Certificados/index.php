<?php
include("../autenticacion.php");
include("../bd.php");
$user_id = $_SESSION['user_id'];
$mensaje = null;
$error = null;
$stmt = $conexion->prepare("
    SELECT c.*, cu.Nombre as CursoNombre, n.NotaFinal
    FROM certificados c
    INNER JOIN cursos cu ON c.IDCurso = cu.ID
    LEFT JOIN NotasCurso n ON n.IDUsuario = c.IDUsuario AND n.IDCurso = c.IDCurso
    WHERE c.IDUsuario = ?
    ORDER BY c.fecha_emision DESC
");
$stmt->execute([$user_id]);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4">🎓 Mis Certificados</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if (empty($certificados)): ?>
        <div class="alert alert-info">
            <p>No tienes certificados aún.</p>
            <p>Completa cursos con una nota final del <strong>70% o superior</strong> para obtener un certificado.</p>
            <a href="../Productos/index.php" class="btn btn-primary mt-2">Explorar cursos</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($certificados as $cert): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white text-center">
                            <i class="bi bi-award" style="font-size: 2rem;"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($cert['CursoNombre']) ?></h5>
                            <p class="card-text">
                                <strong>Fecha de emisión:</strong><br>
                                <?= date('d/m/Y', strtotime($cert['fecha_emision'])) ?>
                            </p>
                            <p class="card-text">
                                <strong>Nota final:</strong><br>
                                <?= $cert['NotaFinal'] !== null ? number_format($cert['NotaFinal'], 2) . '%' : 'No disponible' ?>
                            </p>
                            <p class="card-text">
                                <strong>Código:</strong><br>
                                <code class="small"><?= htmlspecialchars($cert['codigo']) ?></code>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent text-center">
                            <a href="generar.php?curso_id=<?= $cert['IDCurso'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> Ver certificado
                            </a>
                          
                          
                          <!--
                           <button onclick="descargarPDF(<?= $cert['IDCurso'] ?>)" class="btn btn-success btn-sm">
                                <i class="bi bi-download"></i> Descargar PDF
                            </button>   -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-secondary mt-3">
            <strong>Total:</strong> <?= count($certificados) ?> certificado<?= count($certificados) > 1 ? 's' : '' ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function descargarPDF(cursoId) {
        window.location.href = 'generar.php?curso_id=' + cursoId + '&descargar=1';
    }
</script>

<?php include("../footer.php"); ?>