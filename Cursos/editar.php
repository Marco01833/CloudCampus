<?php
include("../autenticacion.php");
include("../bd.php");

$txtID = isset($_GET['txtID']) ? (int)$_GET['txtID'] : 0;
$curso = null;
$error = '';

if ($txtID > 0) {
    $stmt = $conexion->prepare("SELECT * FROM cursos WHERE ID = ?");
    $stmt->execute([$txtID]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$curso) {
        header('Location: ../Cursos_Usuario/contenido.php?id=' . $txtID . '&mensaje=Curso no encontrado');
        exit;
    }
}

if ($_POST) {
    $id = (int) ($_POST['ID'] ?? 0);
    $Nombre = $_POST['Nombre'] ?? '';
    $Descripcion = $_POST['Descripcion'] ?? '';
    $Precio = (float) ($_POST['Precio'] ?? 0);
    $IDUsuario = $curso['IDUsuario'] ?? 0; 
    $imagen_actual = $curso['Imagen'] ?? 'default.jpg';
    $nombreArchivo_imagen = $imagen_actual;

    if (isset($_FILES['Imagen']['name']) && $_FILES['Imagen']['name'] != '') {
        $fecha = new DateTime();
        $nombreArchivo_imagen = $fecha->getTimestamp() . '_' . $_FILES['Imagen']['name'];
        $tmp = $_FILES['Imagen']['tmp_name'];
        if (!is_dir('../Cursos_Usuario/Imagen/')) {
            mkdir('../Cursos_Usuario/Imagen/', 0755, true);
        }
        if (move_uploaded_file($tmp, '../Cursos_Usuario/Imagen/' . $nombreArchivo_imagen)) {
            if ($imagen_actual != 'default.jpg' && file_exists('../Cursos_Usuario/Imagen/' . $imagen_actual)) {
                unlink('../Cursos_Usuario/Imagen/' . $imagen_actual);
            }
        } else {
            $nombreArchivo_imagen = $imagen_actual;
        }
    }

    try {
        $sql = "UPDATE cursos SET 
                IDUsuario = ?, 
                Nombre = ?, 
                Descripcion = ?, 
                Precio = ?, 
                Imagen = ? 
                WHERE ID = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$IDUsuario, $Nombre, $Descripcion, $Precio, $nombreArchivo_imagen, $id]);
        header('Location: ../Cursos_Usuario/contenido.php?id=' . $txtID . '&mensaje=Curso actualizado correctamente');
        exit;
    } catch (PDOException $e) {
        $error = 'Error al actualizar: ' . $e->getMessage();
    }
}

include("../header.php");
?>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Editar Curso</h2>
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del curso</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ID" value="<?= $txtID ?>">
                        
                        <div class="mb-4">
                            <label for="Nombre" class="form-label fw-bold">Nombre:</label>
                            <input type="text" value="<?= htmlspecialchars($curso['Nombre'] ?? '') ?>" class="form-control form-control-lg" name="Nombre" id="Nombre" required>
                        </div>
                        <div class="mb-4">
                            <label for="Descripcion" class="form-label fw-bold">Descripción:</label>
                            <input type="text" value="<?= htmlspecialchars($curso['Descripcion'] ?? '') ?>" class="form-control form-control-lg" name="Descripcion" id="Descripcion">
                        </div>
                        <div class="mb-4">
                            <label for="Precio" class="form-label fw-bold">Precio:</label>
                            <input type="number" step="0.01" value="<?= htmlspecialchars($curso['Precio'] ?? 0) ?>" class="form-control form-control-lg" name="Precio" id="Precio" required>
                        </div>
                        <div class="mb-3">
                            <label for="Imagen" class="form-label">Imagen:</label>
                            <div id="imagePreview" class="text-center mb-3">
                                <?php if (!empty($curso['Imagen']) && file_exists('../Cursos_Usuario/Imagen/' . $curso['Imagen'])): ?>
                                    <img src="../Cursos_Usuario/Imagen/<?= $curso['Imagen'] ?>" class="img-thumbnail border-3 shadow-sm" style="width: auto; height: 200px; object-fit: cover;" alt="Imagen del curso"/>
                                <?php else: ?>
                                    <div class="bg-light border-3 border-dashed d-flex align-items-center justify-content-center rounded" style="width: 200px; height: 200px; margin: 0 auto;">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control" name="Imagen" id="Imagen" accept="image/*" onchange="previewImage(this)">
                            <small class="form-text text-muted">Deje en blanco para mantener la imagen actual. Formatos: JPG, PNG, GIF</small>
                            <?php if (!empty($curso['Imagen'])): ?>
                                <div class="form-text"><i class="bi bi-info-circle"></i> Imagen actual: <?= htmlspecialchars($curso['Imagen']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="../Cursos_Usuario/contenido.php?id=<?= $txtID ?>" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
            img.style.maxWidth = '100%';
            img.style.maxHeight = '200px';
            img.style.border = '2px solid #0d6efd';
            img.style.borderRadius = '5px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include("../footer.php"); ?>