<?php
include("../bd.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $curso_id = (int)$_POST['curso_id'];
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'Pendiente';
    $estados_validos = ['Pendiente', 'Aprobado', 'Rechazado'];

    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conexion->prepare("UPDATE cursos SET Estado = ? WHERE ID = ?");
        $stmt->execute([$nuevo_estado, $curso_id]);
        $mensaje = "Estado actualizado correctamente.";
    } else {
        $mensaje_error = "Estado no válido.";
    }
    header("Location: index.php?mensaje=" . urlencode($mensaje ?? $mensaje_error));
    exit;
}
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$estados_validos_filtro = ['Pendiente', 'Aprobado', 'Rechazado', 'todos'];

if (!in_array($filtro_estado, $estados_validos_filtro)) {
    $filtro_estado = 'todos';
}

if ($filtro_estado == 'todos') {
    $sentencia = $conexion->prepare("
        SELECT c.*, u.Correo as UsuarioCorreo 
        FROM cursos c
        INNER JOIN Usuarios u ON c.IDUsuario = u.ID
        ORDER BY c.ID DESC
    ");
    $sentencia->execute();
} else {
    $sentencia = $conexion->prepare("
        SELECT c.*, u.Correo as UsuarioCorreo 
        FROM cursos c
        INNER JOIN Usuarios u ON c.IDUsuario = u.ID
        WHERE c.Estado = ?
        ORDER BY c.ID DESC
    ");
    $sentencia->execute([$filtro_estado]);
}
$lista_cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<?php if(isset($_GET['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($mensaje_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">Gestión de Cursos</h4>
        <form method="get" action="" class="d-flex align-items-center gap-2">
            <label for="estado" class="mb-0 me-1">Filtrar por estado:</label>
            <select name="estado" id="estado" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="todos" <?= ($filtro_estado == 'todos') ? 'selected' : '' ?>>Todos</option>
                <option value="Pendiente" <?= ($filtro_estado == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="Aprobado" <?= ($filtro_estado == 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
                <option value="Rechazado" <?= ($filtro_estado == 'Rechazado') ? 'selected' : '' ?>>Rechazado</option>
            </select>
            <noscript>
                <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
            </noscript>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Profesor</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Cambiar Estado</th>
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
                            <?php if(!empty($curso['Imagen']) && file_exists("../Cursos_Usuario/Imagen/".$curso['Imagen'])): ?>
                                <img src="../Cursos_Usuario/Imagen/<?= $curso['Imagen'] ?>" width="50" height="50" style="object-fit: cover;" class="rounded" alt="img">
                            <?php else: ?>
                                <img src="../Cursos_Usuario/Imagen/default.jpg" width="50" height="50" style="object-fit: cover;" class="rounded" alt="default">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $estado = $curso['Estado'] ?? 'Pendiente';
                            ?>
                            <span><?= $estado ?></span>
                        </td>
                        <td>
                            <form method="post" action="" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="curso_id" value="<?= $curso['ID'] ?>">
                                <select name="nuevo_estado" class="form-select form-select-sm" style="width: auto;">
                                    <option value="Pendiente" <?= ($estado == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="Aprobado" <?= ($estado == 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
                                    <option value="Rechazado" <?= ($estado == 'Rechazado') ? 'selected' : '' ?>>Rechazado</option>
                                </select>
                                <button type="submit" name="cambiar_estado" class="btn btn-primary btn-sm">Actualizar</button>
                            </form>
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