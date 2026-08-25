<?php include("../bd.php");

$IDCurso = $Titulo = $Tipo = $Archivo = '';
$OrdenContenido = 0;
$Bloqueado = 1;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $IDCurso = (isset($_POST["IDCurso"])) ? (int)$_POST["IDCurso"] : 0;
    $Titulo = (isset($_POST["Titulo"])) ? trim($_POST["Titulo"]) : "";
    $Tipo = (isset($_POST["Tipo"])) ? $_POST["Tipo"] : "";
    $OrdenContenido = (isset($_POST["OrdenContenido"])) ? (int)$_POST["OrdenContenido"] : 0;
    $Bloqueado = (isset($_POST["Bloqueado"])) ? (int)$_POST["Bloqueado"] : 1;
    $Archivo = '';

    if($Tipo == 'archivo') {
        if(isset($_FILES["archivo"]["name"]) && $_FILES["archivo"]["name"] != '') {
            $fecha = new DateTime();
            $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["archivo"]["name"];
            $tmp_archivo = $_FILES["archivo"]['tmp_name'];
            $carpeta_archivos = "./Archivos/";
            if (!file_exists($carpeta_archivos)) mkdir($carpeta_archivos, 0755, true);
            if(move_uploaded_file($tmp_archivo, $carpeta_archivos . $nombre_archivo)) {
                $Archivo = $nombre_archivo;
            }
        }
    }
    elseif($Tipo == 'video') {
        if(isset($_FILES["video"]["name"]) && $_FILES["video"]["name"] != '') {
            $fecha = new DateTime();
            $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["video"]["name"];
            $tmp_video = $_FILES["video"]['tmp_name'];
            $carpeta_videos = "./Video/";
            if (!file_exists($carpeta_videos)) mkdir($carpeta_videos, 0755, true);
            if(move_uploaded_file($tmp_video, $carpeta_videos . $nombre_archivo)) {
                $Archivo = $nombre_archivo;
            }
        }
    }
    elseif($Tipo == 'enlace') {
        $Archivo = (isset($_POST['Archivo'])) ? trim($_POST['Archivo']) : '';
    }
    if(empty($IDCurso) || empty($Titulo) || empty($Tipo) || ($Tipo != 'enlace' && empty($Archivo))) {
        $mensaje_error = "Todos los campos obligatorios deben ser completados.";
    } else {
        $sentencia = $conexion->prepare("INSERT INTO Contenido (IDCurso, Titulo, Tipo, Archivo, OrdenContenido, Bloqueado) 
                                         VALUES (:IDCurso, :Titulo, :Tipo, :Archivo, :OrdenContenido, :Bloqueado)");
        $sentencia->bindParam(":IDCurso", $IDCurso);
        $sentencia->bindParam(":Titulo", $Titulo);
        $sentencia->bindParam(":Tipo", $Tipo);
        $sentencia->bindParam(":Archivo", $Archivo);
        $sentencia->bindParam(":OrdenContenido", $OrdenContenido);
        $sentencia->bindParam(":Bloqueado", $Bloqueado);

        if($sentencia->execute()) {
            header("Location: index.php?mensaje=Contenido creado correctamente");
            exit();
        } else {
            $mensaje_error = "Error al crear el contenido";
        }
    }
}

include("../header.php");
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Crear Nuevo Contenido</h2>
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del contenido</h5>
                </div>
                <div class="card-body p-4">
                    <?php if(isset($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="IDCurso" class="form-label fw-bold">
                                <i class="bi bi-journal-text"></i> Curso:
                            </label>
                            <select name="IDCurso" id="IDCurso" class="form-select" required>
                                <option value="">-- Seleccione un curso --</option>
                                <?php
                                $consulta_cursos = $conexion->query("SELECT ID, Nombre FROM Cursos ORDER BY Nombre");
                                while ($curso = $consulta_cursos->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <option value="<?= $curso['ID'] ?>" <?= ($curso['ID'] == $IDCurso) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($curso['Nombre']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="Titulo" class="form-label fw-bold">
                                <i class="bi bi-card-heading"></i> Título:
                            </label>
                            <input type="text" class="form-control form-control-lg border-2" 
                                   name="Titulo" id="Titulo" required
                                   value="<?= htmlspecialchars($Titulo) ?>"/>
                            <small class="form-text text-muted d-block mt-2">Ingrese el título del contenido</small>
                        </div>

                        <div class="mb-4">
                            <label for="Tipo" class="form-label fw-bold">
                                <i class="bi bi-collection"></i> Tipo de contenido:
                            </label>
                            <select class="form-select form-select-lg border-2" 
                                    name="Tipo" id="Tipo" onchange="mostrarCampoAdecuado()" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="video" <?= ($Tipo == 'video') ? 'selected' : '' ?>>Video</option>
                                <option value="archivo" <?= ($Tipo == 'archivo') ? 'selected' : '' ?>>Archivo</option>
                                <option value="enlace" <?= ($Tipo == 'enlace') ? 'selected' : '' ?>>Enlace</option>
                            </select>
                        </div>

                        <div id="campoVideo" class="mb-4" style="display: none;">
                            <label for="video" class="form-label fw-bold"><i class="bi bi-camera-video"></i> Subir video:</label>
                            <input type="file" class="form-control form-control-lg" name="video" id="video" accept="video/*">
                            <small class="form-text text-muted d-block mt-2">Formatos aceptados: MP4, WebM, OGG</small>
                        </div>

                        <div id="campoArchivo" class="mb-4" style="display: none;">
                            <label for="archivo" class="form-label fw-bold"><i class="bi bi-file-earmark-arrow-up"></i> Subir archivo:</label>
                            <input type="file" class="form-control form-control-lg" name="archivo" id="archivo">
                        </div>

                        <div id="campoEnlace" class="mb-4" style="display: none;">
                            <label for="Archivo" class="form-label fw-bold"><i class="bi bi-link-45deg"></i> URL:</label>
                            <input type="url" class="form-control form-control-lg border-2" 
                                   name="Archivo" id="Archivo" 
                                   value="<?= ($Tipo == 'enlace') ? htmlspecialchars($Archivo) : '' ?>"
                                   placeholder="https://ejemplo.com">
                            <small class="form-text text-muted d-block mt-2">Ingrese la URL completa (incluyendo http:// o https://)</small>
                        </div>

                        <div class="mb-4">
                            <label for="OrdenContenido" class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-up"></i> Orden:
                            </label>
                            <input type="number" class="form-control form-control-lg border-2" 
                                   name="OrdenContenido" id="OrdenContenido" required
                                   value="<?= $OrdenContenido ?>" min="0" step="1"/>
                            <small class="form-text text-muted d-block mt-2">Número de orden para mostrar el contenido</small>
                        </div>

                        <div class="mb-4">
                            <label for="Bloqueado" class="form-label fw-bold">
                                <i class="bi bi-lock"></i> Bloqueado:
                            </label>
                            <select name="Bloqueado" id="Bloqueado" class="form-select" required>
                                <option value="1" <?= ($Bloqueado == 1) ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($Bloqueado == 0) ? 'selected' : '' ?>>No</option>
                            </select>
                            <small class="form-text text-muted d-block mt-2">Indica si el contenido está bloqueado para usuarios sin el plan adecuado</small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="index.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancelar</a>
                            <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarCampoAdecuado() {
    const tipo = document.getElementById('Tipo').value;
    document.getElementById('campoVideo').style.display = 'none';
    document.getElementById('campoArchivo').style.display = 'none';
    document.getElementById('campoEnlace').style.display = 'none';
    if (tipo === 'video') document.getElementById('campoVideo').style.display = 'block';
    else if (tipo === 'archivo') document.getElementById('campoArchivo').style.display = 'block';
    else if (tipo === 'enlace') document.getElementById('campoEnlace').style.display = 'block';
}
document.addEventListener('DOMContentLoaded', mostrarCampoAdecuado);
</script>

<?php include("../footer.php"); ?>