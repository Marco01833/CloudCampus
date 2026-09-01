<?php include("../autenticacion.php");
include("../bd.php");
if(isset($_GET['txtID'])){
    $txtID = $_GET['txtID'];
    $sentencia = $conexion->prepare("DELETE FROM Planes WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $mensaje = "Plan eliminado correctamente";
    header("Location: index.php?mensaje=".$mensaje);
    exit;
}
$sentencia = $conexion->prepare("SELECT ID, Nombre, Precio, DuracionDias, Descuento FROM Planes");
$sentencia->execute();
$lista_planes = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include("../header.php"); ?>
<?php if(isset($_GET['mensaje'])) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <?php echo $_GET['mensaje']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>
<link rel="stylesheet" href="../css/index-planes.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// panel</span>
            <h1>Planes</h1>
            <p>Gestioná los planes de suscripción, precios y descuentos.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Nuevo plan
        </a>
    </div>

    <?php if(isset($_GET['mensaje'])) { ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($_GET['mensaje']); ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php } ?>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscarPlan" placeholder="Buscar por nombre...">
            </div>
            <span class="table-count"><strong id="conteoPlanes"><?php echo count($lista_planes); ?></strong> planes encontrados</span>
        </div>

        <?php if(count($lista_planes) > 0) { ?>

        <div class="table-scroll">
            <table class="data-table" id="tablaPlanes">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="col-price">Precio</th>
                        <th>Duración (días)</th>
                        <th>Descuento</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_planes as $registro) { ?>
                    <tr>
                        <td><div class="dash-table-title"><?php echo htmlspecialchars($registro['Nombre']); ?></div></td>
                        <td class="col-price"><span class="dash-price">$<?php echo number_format($registro['Precio'], 2); ?></span></td>
                        <td class="cell-muted"><?php echo $registro['DuracionDias'] ?? 'N/A'; ?></td>
                        <td>
                            <?php if($registro['Descuento'] > 0): ?>
                                <span class="badge badge-descuento"><i class="fa-solid fa-tag"></i> <?php echo number_format($registro['Descuento'], 2); ?>%</span>
                            <?php else: ?>
                                <span class="cell-muted">0%</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <a class="icon-btn" href="editar.php?txtID=<?php echo $registro['ID']; ?>" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a class="icon-btn icon-btn-danger" href="index.php?txtID=<?php echo $registro['ID']; ?>"
                                   onclick="return confirm('¿Está seguro de eliminar este plan?')" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de planes: <strong><?php echo count($lista_planes); ?></strong>
        </div>

        <?php } else { ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No hay planes registrados todavía.</p>
            <a href="crear.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Crear el primero
            </a>
        </div>

        <?php } ?>

    </div>

</div>

<script>
    const inputBuscar = document.getElementById('buscarPlan');
    const tabla = document.getElementById('tablaPlanes');

    if (inputBuscar && tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        const conteo = document.getElementById('conteoPlanes');

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