<?php
include("../autenticacion.php");
include("../bd.php");

if (isset($_GET['txtID'])) {
    $txtID = (int)$_GET['txtID'];
    $sentencia = $conexion->prepare("DELETE FROM Usuarios WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $mensaje = "Usuario eliminado correctamente";
    header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
    exit;
}

$sentencia = $conexion->prepare("
    SELECT u.ID, u.Correo, u.Estado, u.Verificado,
           r.Nombre AS RolNombre, p.Nombre AS PlanNombre
    FROM Usuarios u
    LEFT JOIN Roles r ON u.IDRol = r.ID
    LEFT JOIN Planes p ON u.IDPlan = p.ID
    ORDER BY u.ID
");
$sentencia->execute();
$lista_usuarios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>
<div class="container mt-4">
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Usuarios</h5>
            <a href="crear.php" class="btn btn-light btn-sm">
                <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Plan</th>
                            <th>Verificado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_usuarios as $registro): ?>
                        <tr>
                            <td><?= $registro['ID'] ?></td>
                            <td><?= htmlspecialchars($registro['Correo']) ?></td>
                            <td><?= htmlspecialchars($registro['RolNombre'] ?? 'Sin rol') ?></td>
                            <td><?= htmlspecialchars($registro['PlanNombre'] ?? 'Sin plan') ?></td>
                            <td>
                                <?= $registro['Verificado'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-warning">No</span>' ?>
                            </td>
                            <td>
                                <?= $registro['Estado'] ? '<span class="badge bg-success">HABILITADO</span>' : '<span class="badge bg-danger">INHABILITADO</span>' ?>
                            </td>
                            <td>
                                <a href="editar.php?txtID=<?= $registro['ID'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <a href="javascript:void(0)" onclick="confirmarEliminar(<?= $registro['ID'] ?>)" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista_usuarios)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay usuarios registrados.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            Total de usuarios: <?= count($lista_usuarios) ?>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        window.location.href = 'usuarios.php?txtID=' + id;
    }
}
</script>

<?php include("../footer.php"); ?>