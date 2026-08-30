<?php
include("../autenticacion.php");
include("../bd.php");
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conexion->prepare("DELETE FROM categoria WHERE IDCategoria = ?");
        $stmt->execute([$id]);
        $mensaje = "Categoría eliminada correctamente.";
        header("Location: index.php?mensaje=" . urlencode($mensaje));
        exit;
    } catch (PDOException $e) {
        $mensaje_error = "No se puede eliminar la categoría porque tiene cursos asociados.";
        header("Location: index.php?mensaje=" . urlencode($mensaje_error));
        exit;
    }
}

$sentencia = $conexion->prepare("SELECT * FROM categoria ORDER BY IDCategoria ASC");
$sentencia->execute();
$lista_categorias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<div class="container mt-4">
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-tags"></i> Gestión de Categorías</h4>
            <a href="crear.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Nueva Categoría
            </a>
        </div>
        <div class="card-body">
            <?php if (count($lista_categorias) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_categorias as $categoria): ?>
                            <tr>
                                <td><?= $categoria['IDCategoria'] ?></td>
                                <td><?= htmlspecialchars($categoria['Nombre']) ?></td>
                                <td><?= htmlspecialchars($categoria['Descripcion'] ?? '') ?></td>
                                <td>
                                    <a href="editar.php?txtID=<?= $categoria['IDCategoria'] ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <a href="index.php?accion=eliminar&id=<?= $categoria['IDCategoria'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('¿Está seguro de eliminar esta categoría?')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No hay categorías registradas.</div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-muted">Total de categorías: <?= count($lista_categorias) ?></div>
    </div>
</div>

<?php include("../footer.php"); ?>