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
    $nombre = trim($_POST['Nombres'] ?? '');
    $apellidos = trim($_POST['Apellidos'] ?? '');
    $telefono = trim($_POST['Telefono'] ?? '');
    $fecha_nacimiento = $_POST['FechaNacimiento'] ?? '';
    $genero = $_POST['Genero'] ?? '';
    $pais = trim($_POST['Pais'] ?? '');
    $ciudad = trim($_POST['Ciudad'] ?? '');
    $direccion = trim($_POST['Direccion'] ?? '');
    $foto_actual = $datos_personales['Foto'] ?? '';
    $nuevo_nombre_foto = $foto_actual; 

    if (isset($_FILES['Foto']) && $_FILES['Foto']['error'] === UPLOAD_ERR_OK) {
        $archivo_tmp = $_FILES['Foto']['tmp_name'];
        $nombre_original = $_FILES['Foto']['name'];
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $tamano_maximo = 2 * 1024 * 1024; 

        if (!in_array($extension, $extensiones_permitidas)) {
            $error = "Formato no permitido. Solo JPG, PNG y GIF.";
        } 
        elseif ($_FILES['Foto']['size'] > $tamano_maximo) {
            $error = "El archivo excede el tamaño máximo de 2MB.";
        } 
        else {
            $fecha = new DateTime();
            $nuevo_nombre_foto = $fecha->getTimestamp() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $nombre_original);
            $destino = './Imagen/' . $nuevo_nombre_foto;
            if (!is_dir('./Imagen/')) {
                mkdir('./Imagen/', 0755, true);
            }

            if (move_uploaded_file($archivo_tmp, $destino)) {
                if (!empty($foto_actual) && $foto_actual !== 'default_profile.png' && file_exists('./Imagen/' . $foto_actual)) {
                    unlink('./Imagen/' . $foto_actual);
                }
            } else {
                $error = "Error al subir la foto. Inténtalo de nuevo.";
                $nuevo_nombre_foto = $foto_actual; 
            }
        }
    }

    if (!isset($error)) {
        $sql = "UPDATE DatosPersonales 
                SET Nombre = ?, 
                    Apellidos = ?,
                    Telefono = ?,
                    FechaNacimiento = ?,
                    Genero = ?,
                    Pais = ?,
                    Ciudad = ?,
                    Direccion = ?,
                    Foto = ?
                WHERE IDUsuario = ?";
        $sentencia = $conexion->prepare($sql);
        $resultado = $sentencia->execute([
            $nombre, $apellidos, $telefono, $fecha_nacimiento,
            $genero, $pais, $ciudad, $direccion,
            $nuevo_nombre_foto, $usuario_id
        ]);

        if ($resultado) {
            header("Location: index.php?mensaje=Perfil actualizado correctamente");
            exit;
        } else {
            $error = "Error al actualizar el perfil. Por favor, inténtalo de nuevo.";
        }
    }
}

include("../header.php");
?>
<link rel="stylesheet" href="perfil.css">
<div class="card">
    <div class="card-header">
        <h4>Datos del Perfil</h4>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="perfilForm" action="" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email:</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($datos_personales['usuario_email'] ?? ''); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto de perfil</label>
                        <div class="text-center mb-2" id="fotoContainer">
                            <?php 
                            $foto_actual = $datos_personales['Foto'] ?? 'default_profile.png';
                            $foto_path = './Imagen/' . $foto_actual;
                            if (!empty($foto_actual) && file_exists($foto_path)) {
                                echo '<img src="' . $foto_path . '" id="foto_actual" class="rounded-circle border" style="width: 180px; height: 180px; object-fit: cover;" alt="Foto de perfil">';
                            } else {
                                echo '<i class="bi bi-person-circle" id="foto_actual" style="font-size: 150px; color: #ccc;"></i>';
                            }
                            ?>
                        </div>
                        <div class="foto-upload" style="display: none;">
                            <input type="file" class="form-control" id="foto" name="Foto" accept="image/*" disabled>
                            <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</small>
                        </div>
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
    const campos = document.querySelectorAll('#perfilForm input:not([type="email"]), #perfilForm select, #perfilForm textarea');

    function habilitarEdicion() {
        campos.forEach(campo => campo.disabled = false);
        document.querySelector('.foto-upload').style.display = 'block';
        btnEditar.style.display = 'none';
        btnCancelar.style.display = 'inline-block';
        btnGuardar.style.display = 'inline-block';
    }

    function cancelarEdicion() {
        location.reload(); 
    }

    btnEditar.addEventListener('click', habilitarEdicion);
    btnCancelar.addEventListener('click', cancelarEdicion);
    document.getElementById('foto').addEventListener('change', function(e) {
        const container = document.getElementById('fotoContainer');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                container.innerHTML = '<img src="' + ev.target.result + '" id="foto_actual" class="rounded-circle border" style="width: 150px; height: 150px; object-fit: cover;" alt="Nueva foto">';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>

<?php include("../footer.php"); ?>