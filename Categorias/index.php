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
<link rel="stylesheet" href="../css/index-planes.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// gestión</span>
            <h1>Categorías</h1>
            <p>Administra las categorías de cursos disponibles en la plataforma.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nueva Categoría
        </a>
    </div>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($_GET['mensaje']); ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscarCategoria" placeholder="Buscar por nombre...">
            </div>
            <span class="table-count"><strong id="conteoCategoria"><?php echo count($lista_categorias); ?></strong> categorías encontradas</span>
        </div>

        <?php if(count($lista_categorias) > 0) { ?>

        <div class="table-scroll">
            <table class="data-table" id="tablaCategoria">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_categorias as $categoria): ?>
                    <tr>
                        <td class="cell-id">#<?php echo $categoria['IDCategoria']; ?></td>
                        <td><div class="dash-table-title"><?php echo htmlspecialchars($categoria['Nombre']); ?></div></td>
                        <td class="cell-muted"><?php echo htmlspecialchars($categoria['Descripcion'] ?? 'Sin descripción'); ?></td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <a class="icon-btn" href="editar.php?txtID=<?php echo $categoria['IDCategoria']; ?>" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a class="icon-btn icon-btn-danger" href="index.php?accion=eliminar&id=<?php echo $categoria['IDCategoria']; ?>"
                                   onclick="return confirm('¿Está seguro de eliminar esta categoría?')" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de categorías: <strong><?php echo count($lista_categorias); ?></strong>
        </div>

        <?php } else { ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No hay categorías registradas todavía.</p>
            <a href="crear.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Crear la primera
            </a>
        </div>

        <?php } ?>

    </div>

</div>

<script>
    const inputBuscar = document.getElementById('buscarCategoria');
    const tabla = document.getElementById('tablaCategoria');

    if (inputBuscar && tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        const conteo = document.getElementById('conteoCategoria');

        inputBuscar.addEventListener('input', () => {
            const texto = inputBuscar.value.trim().toLowerCase();
            let visibles = 0;

            filas.forEach(fila => {
                const nombre = fila.children[1].textContent.toLowerCase();
                const coincide = nombre.includes(texto);
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            if (conteo) conteo.textContent = visibles;
        });
    }
</script>

<?php include("../footer.php"); ?>