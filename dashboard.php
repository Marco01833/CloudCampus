<?php 
include("bd.php");

$cursos_mas_vendidos = $conexion->query("
    SELECT c.*, COUNT(i.ID) as total_ventas 
    FROM Cursos c
    LEFT JOIN Inscripciones i ON c.ID = i.IDCurso
    WHERE i.Estado = 1
    GROUP BY c.ID
    ORDER BY total_ventas DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$cursos_destacados = $conexion->query("
    SELECT * FROM Cursos 
    ORDER BY ID DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Cursos Destacados y Más Vendidos";
include("header.php");
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-5 fw-bold mb-3">Explora Nuestros Cursos</h1>
            <p class="lead">Descubre los cursos más populares y destacados</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Cursos Destacados</h2>
                
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php foreach ($cursos_destacados as $curso): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($curso['Imagen'])): ?>
                            <img src="Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($curso['Nombre']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?= substr(htmlspecialchars($curso['Descripcion'] ?? 'Descripción no disponible'), 0, 80) ?>...</p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="h5 mb-0 text-primary fw-bold">$<?= number_format($curso['Precio'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-3">Los más vendidos</h2>
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%"></th>
                                <th style="width: 35%">Curso</th>
                                <th style="width: 30%">Descripción</th>
                                <th class="text-end" style="width: 15%">Precio</th>
                                <th class="text-center" style="width: 10%">Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos_mas_vendidos as $curso): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($curso['Imagen'])): ?>
                                        <img src="Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" width="50" height="50" style="object-fit: cover;" class="rounded" alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-book text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><h6 class="mb-1 fw-bold"><?= htmlspecialchars($curso['Nombre']) ?></h6><small class="text-muted">ID: <?= $curso['ID'] ?></small></td>
                                <td><p class="mb-0 small text-muted"><?= substr(htmlspecialchars($curso['Descripcion'] ?? 'Sin descripción'), 0, 100) ?>...</p></td>
                                <td class="text-end"><span class="fw-bold text-primary">$<?= number_format($curso['Precio'] ?? 0, 2) ?></span></td>
                                <td class="text-center"><span class="badge bg-primary rounded-pill px-3 py-2"><?= $curso['total_ventas'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>