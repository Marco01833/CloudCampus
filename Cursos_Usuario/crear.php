<?php
include("../autenticacion.php");
include("../bd.php");

$categorias = [];
$stmtCats = $conexion->query("SELECT IDCategoria, Nombre FROM categoria ORDER BY IDCategoria ASC");
$categorias = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

if($_POST){
    $IDUsuario = $_SESSION['user_id'];
    $Nombre = (isset($_POST["Nombre"])) ? $_POST["Nombre"] : "";
    $Descripcion = (isset($_POST["Descripcion"])) ? $_POST["Descripcion"] : "";
    $nivel = (isset($_POST["nivel"])) ? $_POST["nivel"] : "";
    $IDCategoria = (isset($_POST["IDCategoria"])) ? $_POST["IDCategoria"] : "";
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
        $stmt = $conexion->prepare("INSERT INTO cursos (IDUsuario, Nombre, Descripcion, nivel, IDCategoria, Precio, Imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$IDUsuario, $Nombre, $Descripcion, $nivel, $IDCategoria, $Precio, $nombreArchivo_imagen]);

        header("Location: index.php?mensaje=Curso creado correctamente");
        exit;
    } catch (PDOException $e) {
        $error = "Error al crear el curso: " . $e->getMessage();
    }
}

include("../header.php");
?>
<link rel="stylesheet" href="../css/crear-curso.css">

<div class="admin-wrap">
    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// nuevo contenido</span>
            <h1>Nuevo curso</h1>
            <p>Cargá la información básica y una imagen de portada para publicar tu curso.</p>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert-box error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars($error) ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <i class="fa-solid fa-graduation-cap"></i> Datos del curso
        </div>

        <div class="admin-card-body">
            <form action="" method="post" enctype="multipart/form-data">

                <div class="field-group">
                    <label for="Nombre">Nombre del curso</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-heading input-icon"></i>
                        <input type="text" name="Nombre" id="Nombre" placeholder="Ej: Python desde cero" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Descripcion">Descripción</label>
                    <textarea name="Descripcion" id="Descripcion" rows="4"
                              placeholder="Contá de qué trata el curso, a quién está dirigido y qué van a aprender..."></textarea>
                </div>

                <div class="field-group mb-3">
                    <label for="nivel" class="fw-semibold text-secondary mb-1">Nivel</label>
                    <div class="input-wrapper position-relative">
                        <i class="fa-solid fa-level-up-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="z-index: 4; font-size: 0.9rem;"></i>
                        <select name="nivel" id="nivel" class="form-select ps-5" required>
                            <option value="">Seleccionar nivel</option>
                            <option value="Básico">Básico</option>
                            <option value="Intermedio">Intermedio</option>
                            <option value="Avanzado">Avanzado</option>
                        </select>
                    </div>
                </div>
                <div class="field-group mb-3">
                    <label for="categoria" class="fw-semibold text-secondary mb-1">Categoría</label>
                    <div class="input-wrapper position-relative">
                        <i class="fa-solid fa-tags position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="z-index: 4; font-size: 0.9rem;"></i>
                        <select name="IDCategoria" id="categoria" class="form-select ps-5" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['IDCategoria'] ?>"><?= htmlspecialchars($cat['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Precio">Precio en Dolares</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-dollar-sign input-icon"></i>
                        <input type="number" step="0.01" min="0" name="Precio" id="Precio" placeholder="0.00" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Imagen">Imagen de portada</label>

                    <label for="Imagen" class="dropzone" id="dropzone">
                        <div id="imagePreview" class="dropzone-preview"></div>
                        <div class="dropzone-placeholder" id="dropzonePlaceholder">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Hacé clic para subir una imagen</span>
                            <small>JPG, PNG o GIF</small>
                        </div>
                    </label>
                    <input type="file" name="Imagen" id="Imagen" accept="image/*" onchange="previewImage(this)" hidden>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                    <a class="btn btn-ghost" href="index.php" role="button">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('dropzonePlaceholder');
    preview.innerHTML = '';

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        placeholder.style.display = 'flex';
        preview.style.display = 'none';
    }
}
</script>

<?php include("../footer.php"); ?>