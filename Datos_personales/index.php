<?php 
include("../autenticacion.php");
include("../bd.php");
$usuario_id = $_SESSION['user_id'];
$sentencia = $conexion->prepare("
    SELECT dp.*, 
           u.Correo AS usuario_email,
           CONCAT(dp.Nombre, ' ', dp.Apellidos) AS usuario_nick
    FROM DatosPersonales dp
    LEFT JOIN Usuarios u ON dp.IDUsuario = u.ID
    WHERE dp.IDUsuario = ?
");
$sentencia->execute([$usuario_id]);
$datos_personales = $sentencia->fetch(PDO::FETCH_ASSOC);

if (!$datos_personales) {
    $stmt = $conexion->prepare("INSERT INTO DatosPersonales (IDUsuario) VALUES (?)");
    $stmt->execute([$usuario_id]);
    $sentencia->execute([$usuario_id]);
    $datos_personales = $sentencia->fetch(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['Nombres'] ?? '';
    $apellidos = $_POST['Apellidos'] ?? '';
    $telefono = $_POST['Telefono'] ?? '';
    $fecha_nacimiento = $_POST['FechaNacimiento'] ?? '';
    $genero = $_POST['Genero'] ?? '';
    $pais = $_POST['Pais'] ?? '';
    $ciudad = $_POST['Ciudad'] ?? '';
    $direccion = $_POST['Direccion'] ?? '';
    $sentencia = $conexion->prepare("
        UPDATE DatosPersonales 
        SET Nombre = ?, 
            Apellidos = ?,
            Telefono = ?,
            FechaNacimiento = ?,
            Genero = ?,
            Pais = ?,
            Ciudad = ?,
            Direccion = ?
        WHERE IDUsuario = ?
    ");
    if ($sentencia->execute([$nombre, $apellidos, $telefono, $fecha_nacimiento, 
                            $genero, $pais, $ciudad, $direccion, $usuario_id])) {
        header("Location: index.php?mensaje=Perfil actualizado correctamente");
        exit;
    } else {
        $error = "Error al actualizar el perfil. Por favor, inténtalo de nuevo.";
    }
}
include("../header.php");
?>
<div class="card">
    <div class="card-header">
        <h4>Editar Perfil</h4>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form id="perfilForm" action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email:</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($datos_personales['usuario_email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="nombres" class="form-label fw-bold">Nombre:</label>
                        <input type="text" class="form-control" id="nombres" name="Nombres" 
                               value="<?php echo htmlspecialchars($datos_personales['Nombre'] ?? ''); ?>" disabled required>
                    </div>
                    <div class="mb-3">
                        <label for="apellidos" class="form-label fw-bold">Apellidos:</label>
                        <input type="text" class="form-control" id="apellidos" name="Apellidos" 
                               value="<?php echo htmlspecialchars($datos_personales['Apellidos'] ?? ''); ?>" disabled required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono:</label>
                        <input type="tel" class="form-control" id="telefono" name="Telefono" 
                               value="<?php echo htmlspecialchars($datos_personales['Telefono'] ?? ''); ?>" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fecha_nacimiento" class="form-label fw-bold">Fecha de Nacimiento:</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="FechaNacimiento" 
                               value="<?php echo htmlspecialchars($datos_personales['FechaNacimiento'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="genero" class="form-label fw-bold">Género:</label>
                        <select class="form-select" id="genero" name="Genero" disabled>
                            <option value="">Seleccione...</option>
                            <option value="Masculino" <?php echo (isset($datos_personales['Genero']) && $datos_personales['Genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo (isset($datos_personales['Genero']) && $datos_personales['Genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pais" class="form-label fw-bold">País:</label>
                        <input type="text" class="form-control" id="pais" name="Pais" 
                               value="<?php echo htmlspecialchars($datos_personales['Pais'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="ciudad" class="form-label fw-bold">Ciudad:</label>
                        <input type="text" class="form-control" id="ciudad" name="Ciudad" 
                               value="<?php echo htmlspecialchars($datos_personales['Ciudad'] ?? ''); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label fw-bold">Dirección:</label>
                        <textarea class="form-control" id="direccion" name="Direccion" rows="2" disabled><?php echo htmlspecialchars($datos_personales['Direccion'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="button" id="btnEditar" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Editar
                </button>
                <a href="index.php" id="btnCancelar" class="btn btn-secondary me-2" style="display:none;">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" id="btnGuardar" class="btn btn-success" style="display:none;">
                    <i class="bi bi-save me-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEditar = document.getElementById('btnEditar');
    const btnCancelar = document.getElementById('btnCancelar');
    const btnGuardar = document.getElementById('btnGuardar');
    const camposEditables = document.querySelectorAll('#perfilForm input:not([disabled]), #perfilForm select, #perfilForm textarea');
    const campos = document.querySelectorAll('#perfilForm input:not([type="email"]), #perfilForm select, #perfilForm textarea');

    function habilitarEdicion() {
        campos.forEach(campo => campo.disabled = false);
        btnEditar.style.display = 'none';
        btnCancelar.style.display = 'inline-block';
        btnGuardar.style.display = 'inline-block';
    }
    function cancelarEdicion() {
        location.reload();
    }
    btnEditar.addEventListener('click', habilitarEdicion);
    btnCancelar.addEventListener('click', cancelarEdicion);
});
</script>
<?php include("../footer.php"); ?>