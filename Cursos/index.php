<?php
include("../bd.php");

if(isset($_GET['txtID'])){
    $txtID = (int)$_GET['txtID'];

    $sentencia = $conexion->prepare("SELECT Imagen FROM Cursos WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    if($registro && !empty($registro["Imagen"]) && $registro["Imagen"] != 'default.jpg'){
        if(file_exists("./Imagen/".$registro["Imagen"])){
            unlink("./Imagen/".$registro["Imagen"]);
        }
    }
    $sentencia = $conexion->prepare("DELETE FROM Cursos WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();

    header("Location: index.php");
    exit;
}
$sentencia = $conexion->prepare("
    SELECT c.*, u.Correo as UsuarioCorreo 
    FROM Cursos c
    INNER JOIN Usuarios u ON c.IDUsuario = u.ID
    ORDER BY c.ID DESC
");
$sentencia->execute();
$lista_cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<br>
<?php if(isset($_GET['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <a class="btn btn-outline-primary" href="crear.php" role="button">
            <i class="bi bi-person-plus-fill"></i> Nuevo Curso
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Profesor</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_cursos as $curso): ?>
                    <tr>
                        <td><?= $curso['ID'] ?></td>
                        <td><?= htmlspecialchars($curso['UsuarioCorreo']) ?></td>
                        <td><?= htmlspecialchars($curso['Nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['Descripcion'] ?? '') ?></td>
                        <td>$<?= number_format($curso['Precio'], 2) ?></td>
                        <td>
                            <?php if(!empty($curso['Imagen']) && file_exists("./Imagen/".$curso['Imagen'])): ?>
                                <img src="./Imagen/<?= $curso['Imagen'] ?>" width="50" height="50" style="object-fit: cover;" class="rounded" alt="img">
                            <?php else: ?>
                                <img src="./Imagen/default.jpg" width="50" height="50" style="object-fit: cover;" class="rounded" alt="default">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-outline-primary btn-sm" href="editar.php?txtID=<?= $curso['ID'] ?>" role="button">
                                <i class="bi bi-pencil-square"></i>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                                </svg>
                            </a>
                            <a class="btn btn-outline-danger btn-sm" href="index.php?txtID=<?= $curso['ID'] ?>" onclick="return confirm('¿Estás seguro?')" role="button">
                                <i class="bi bi-trash-fill"></i>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">Total de cursos: <?= count($lista_cursos) ?></div>
</div>

<?php include("../footer.php"); ?>