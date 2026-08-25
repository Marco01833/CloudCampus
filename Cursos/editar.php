<?php
include("../bd.php");

if(isset($_GET["txtID"])){
    $txtID = (int)$_GET["txtID"];
    $sentencia = $conexion->prepare("SELECT * FROM Cursos WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);

    if($registro){
        $IDCurso = $registro["ID"];
        $IDUsuario = $registro["IDUsuario"];
        $Nombre = $registro["Nombre"];
        $Descripcion = $registro["Descripcion"];
        $Precio = $registro["Precio"];
        $Imagen = $registro["Imagen"];
    } else {
        header("Location: index.php");
        exit;
    }
}

if($_POST){
    $txtID = (isset($_POST["ID"])) ? (int)$_POST["ID"] : 0;
    $IDUsuario = (isset($_POST["IDUsuario"])) ? (int)$_POST["IDUsuario"] : 0;
    $Nombre = (isset($_POST["Nombre"])) ? $_POST["Nombre"] : "";
    $Descripcion = (isset($_POST["Descripcion"])) ? $_POST["Descripcion"] : "";
    $Precio = (isset($_POST["Precio"])) ? (float)$_POST["Precio"] : 0;
    $verificar = $conexion->prepare("SELECT ID FROM Cursos WHERE IDUsuario = :uid AND ID != :id");
    $verificar->bindParam(":uid", $IDUsuario);
    $verificar->bindParam(":id", $txtID);
    $verificar->execute();
    if($verificar->rowCount() > 0){
        $error = "El profesor seleccionado ya tiene otro curso asignado.";
    }

    if(empty($error)){
        $sentencia = $conexion->prepare("SELECT Imagen FROM Cursos WHERE ID = :id");
        $sentencia->bindParam(":id", $txtID);
        $sentencia->execute();
        $imagen_actual = $sentencia->fetch(PDO::FETCH_LAZY)["Imagen"];

        $Imagen = $imagen_actual; 

        if(isset($_FILES["Imagen"]["name"]) && $_FILES["Imagen"]["name"] != ''){
            $fecha_ = new DateTime();
            $nombreArchivo_imagen = $fecha_->getTimestamp() . "_" . $_FILES["Imagen"]["name"];
            $tmp_imagen = $_FILES["Imagen"]['tmp_name'];

            if(move_uploaded_file($tmp_imagen, "./Imagen/" . $nombreArchivo_imagen)){
                if(!empty($imagen_actual) && $imagen_actual != 'default.jpg' && file_exists("./Imagen/" . $imagen_actual)){
                    unlink("./Imagen/" . $imagen_actual);
                }
                $Imagen = $nombreArchivo_imagen;
            }
        }

        $sentencia = $conexion->prepare("UPDATE Cursos SET
            IDUsuario = :IDUsuario,
            Nombre = :Nombre,
            Descripcion = :Descripcion,
            Precio = :Precio,
            Imagen = :Imagen
            WHERE ID = :id");

        $sentencia->bindParam(":IDUsuario", $IDUsuario);
        $sentencia->bindParam(":Nombre", $Nombre);
        $sentencia->bindParam(":Descripcion", $Descripcion);
        $sentencia->bindParam(":Precio", $Precio);
        $sentencia->bindParam(":Imagen", $Imagen);
        $sentencia->bindParam(":id", $txtID);
        $sentencia->execute();

        header("Location: index.php");
        exit;
    }
}

$usuarios_disponibles = $conexion->query("
    SELECT u.ID, u.Correo 
    FROM Usuarios u
    LEFT JOIN Cursos c ON u.ID = c.IDUsuario
    WHERE u.IDRol = 3 AND (c.ID IS NULL OR c.ID = $txtID)
    ORDER BY u.Correo
")->fetchAll(PDO::FETCH_ASSOC);

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
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ID" value="<?= $txtID ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">ID:</label>
                            <input type="text" value="<?= $txtID ?>" class="form-control form-control-lg bg-light" disabled />
                        </div>

                        <div class="mb-3">
                            <label for="IDUsuario" class="form-label fw-bold">Profesor:</label>
                            <select name="IDUsuario" id="IDUsuario" class="form-select form-select-lg" required>
                                <option value="">Seleccione un profesor</option>
                                <?php foreach($usuarios_disponibles as $usuario): ?>
                                    <option value="<?= $usuario['ID'] ?>" <?= ($usuario['ID'] == $IDUsuario) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($usuario['Correo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Solo se muestran profesores sin curso o el actual.</small>
                        </div>

                        <div class="mb-4">
                            <label for="Nombre" class="form-label fw-bold">Nombre:</label>
                            <input type="text" value="<?= htmlspecialchars($Nombre ?? '') ?>" class="form-control form-control-lg" name="Nombre" id="Nombre" required>
                        </div>

                        <div class="mb-4">
                            <label for="Descripcion" class="form-label fw-bold">Descripción:</label>
                            <input type="text" value="<?= htmlspecialchars($Descripcion ?? '') ?>" class="form-control form-control-lg" name="Descripcion" id="Descripcion">
                        </div>

                        <div class="mb-4">
                            <label for="Precio" class="form-label fw-bold">Precio:</label>
                            <input type="number" step="0.01" value="<?= htmlspecialchars($Precio ?? 0) ?>" class="form-control form-control-lg" name="Precio" id="Precio" required>
                        </div>

                        <div class="mb-3">
                            <label for="Imagen" class="form-label">Imagen:</label>
                            <div id="imagePreview" class="text-center mb-3">
                                <?php if(!empty($Imagen) && file_exists("./Imagen/".$Imagen)): ?>
                                    <img src="./Imagen/<?= $Imagen ?>" class="img-thumbnail border-3 shadow-sm" style="width: auto; height: 200px; object-fit: cover;" alt="Imagen del curso"/>
                                <?php else: ?>
                                    <div class="bg-light border-3 border-dashed d-flex align-items-center justify-content-center rounded" style="width: 200px; height: 200px; margin: 0 auto;">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <input type="file" class="form-control" name="Imagen" id="Imagen" accept="image/*" onchange="previewImage(this)">
                            <small class="form-text text-muted">Deje en blanco para mantener la imagen actual. Formatos: JPG, PNG, GIF</small>
                            <?php if(!empty($Imagen)): ?>
                                <div class="form-text"><i class="bi bi-info-circle"></i> Imagen actual: <?= htmlspecialchars($Imagen) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="index.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancelar</a>
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
    } else {
    }
}
</script>

<?php include("../footer.php"); ?>