<?php include("../autenticacion.php");
include("../bd.php");

if(isset($_GET['txtID'])){
    $txtID = (int)$_GET['txtID'];

    // Obtener información del archivo para eliminarlo físicamente
    $sentencia = $conexion->prepare("SELECT Archivo, Tipo FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    if($registro){
        $archivo = $registro['Archivo'];
        $tipo = $registro['Tipo'];
        if($tipo == 'video' && !empty($archivo) && file_exists("./Video/".$archivo)) {
            unlink("./Video/".$archivo);
        } elseif($tipo == 'archivo' && !empty($archivo) && file_exists("./Archivos/".$archivo)) {
            unlink("./Archivos/".$archivo);
        }
    }

    $sentencia = $conexion->prepare("DELETE FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location: index.php?mensaje=Registro eliminado");
    exit;
}

// Listar contenido con nombre del curso
$sentencia = $conexion->prepare("
    SELECT c.*, cu.Nombre as CursoNombre 
    FROM Contenido c
    LEFT JOIN Cursos cu ON c.IDCurso = cu.ID
    ORDER BY c.IDCurso, c.OrdenContenido
");
$sentencia->execute();
$lista_contenido = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../header.php") ?>

<?php if(isset($_GET['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <a class="btn btn-outline-primary" href="crear.php" role="button">
            <i class="bi bi-plus-circle"></i> Nuevo
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Curso</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Archivo/URL</th>
                        <th>Orden</th>
                        <th>Bloqueado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_contenido as $registro): ?>
                    <tr>
                        <td><?= $registro['ID'] ?></td>
                        <td><?= htmlspecialchars($registro['CursoNombre'] ?? 'Sin curso') ?></td>
                        <td><?= htmlspecialchars($registro['Titulo']) ?></td>
                        <td><?= ucfirst($registro['Tipo']) ?></td>
                        <td>
                            <?php if($registro['Tipo'] == 'enlace'): ?>
                                <a href="<?= htmlspecialchars($registro['Archivo']) ?>" target="_blank"><?= htmlspecialchars($registro['Archivo']) ?></a>
                            <?php elseif(!empty($registro['Archivo']) && file_exists("./".ucfirst($registro['Tipo'])."s/".$registro['Archivo'])): ?>
                                <a href="./<?= ucfirst($registro['Tipo']) ?>s/<?= htmlspecialchars($registro['Archivo']) ?>" target="_blank">
                                    <?= htmlspecialchars($registro['Archivo']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($registro['Archivo']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $registro['OrdenContenido'] ?></td>
                        <td><?= ($registro['Bloqueado'] == 1) ? 'Sí' : 'No' ?></td>
                        <td>
                            <a class="btn btn-outline-primary btn-sm" href="editar.php?txtID=<?= $registro['ID'] ?>" role="button">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a class="btn btn-outline-danger btn-sm" href="index.php?txtID=<?= $registro['ID'] ?>" 
                               onclick="return confirm('¿Está seguro de eliminar este contenido?')" role="button">
                                <i class="bi bi-trash-fill">Eliminar</i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">Total de contenidos: <?= count($lista_contenido) ?></div>
</div>