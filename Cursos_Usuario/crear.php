<?php
include("../autenticacion.php");
include("../bd.php");

if($_POST){
    $IDUsuario = $_SESSION['user_id'];
    $Nombre = (isset($_POST["Nombre"])) ? $_POST["Nombre"] : "";
    $Descripcion = (isset($_POST["Descripcion"])) ? $_POST["Descripcion"] : "";
    $Precio = (isset($_POST["Precio"])) ? (float)$_POST["Precio"] : 0;
    $Imagen = (isset($_FILES["Imagen"]['name'])) ? $_FILES["Imagen"]['name'] : "";

    $fecha_ = new DateTime();
    $nombreArchivo_imagen = ($Imagen != "") ? $fecha_->getTimestamp() . "_" . $_FILES["Imagen"]['name'] : "";
    $tmp_imagen = $_FILES["Imagen"]['tmp_name'];

    if($tmp_imagen != ""){
        if(!is_dir("./Imagen/")) mkdir("./Imagen/", 0755, true);
        move_uploaded_file($tmp_imagen, "./Imagen/".$nombreArchivo_imagen);
    } else {
        $nombreArchivo_imagen = "default.jpg";
    }

    try {
        $stmt = $conexion->prepare("INSERT INTO cursos (IDUsuario, Nombre, Descripcion, Precio, Imagen) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$IDUsuario, $Nombre, $Descripcion, $Precio, $nombreArchivo_imagen]);

        header("Location: index.php?mensaje=Curso creado correctamente");
        exit;
    } catch (PDOException $e) {
        $error = "Error al crear el curso: " . $e->getMessage();
    }
}

include("../header.php");
?>
<br><br>
<div class="card">
    <div class="card-header">Nuevo Curso</div>
    <div class="card-body">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="Nombre" class="form-label">Nombre:</label>
                <input type="text" class="form-control" name="Nombre" id="Nombre" placeholder="Nombre del curso" required>
            </div>

            <div class="mb-3">
                <label for="Descripcion" class="form-label">Descripción:</label>
                <input type="text" class="form-control" name="Descripcion" id="Descripcion" placeholder="Descripción del curso">
            </div>

            <div class="mb-3">
                <label for="Precio" class="form-label">Precio:</label>
                <input type="number" step="0.01" class="form-control" name="Precio" id="Precio" placeholder="0.00" required>
            </div>

            <div class="mb-3">
                <label for="Imagen" class="form-label">Imagen:</label>
                <div id="imagePreview" class="text-center mt-3"></div>
                <input type="file" value="IFNULL(NULLIF(p_imagen, ''), 'default.jpg')" class="form-control" name="Imagen" id="Imagen" accept="image/*" onchange="previewImage(this)">
                <small class="form-text text-muted">Formatos: JPG, PNG, GIF</small>
            </div>

            <button type="submit" class="btn btn-success"><i class="bi bi-bookmarks-fill"></i> Guardar</button>
            <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.maxWidth = '200px';
            img.style.border = '2px solid #0d6efd';
            img.style.borderRadius = '5px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include("../footer.php"); ?>