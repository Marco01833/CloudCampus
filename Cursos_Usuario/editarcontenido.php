<?php include("../autenticacion.php");
include("../bd.php");

$txtID = isset($_GET["txtID"]) ? $_GET["txtID"] : 0;
$IDCurso = 0;
$Titulo = '';
$Tipo = '';
$Archivo = '';
$Bloqueado = 1;

if($txtID > 0){
    $sentencia = $conexion->prepare("SELECT * FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    
    if($registro){
        $IDCurso   = $registro["IDCurso"];
        $Titulo    = $registro["Titulo"];
        $Tipo      = $registro["Tipo"];
        $Archivo   = $registro["Archivo"];
        $Bloqueado = $registro["Bloqueado"];
    } else {
        header("Location: index.php?mensaje=Contenido no encontrado");
        exit;
    }
} else {
    header("Location: index.php?mensaje=ID de contenido no proporcionado");
    exit;
}

if($_POST){
    $txtID        = $_POST["ID"] ?? 0;
    $IDCurso      = $_POST["IDCurso"] ?? 0;
    $Titulo       = $_POST["Titulo"] ?? '';
    $Tipo         = $_POST["Tipo"] ?? '';
    $Bloqueado    = $_POST["Bloqueado"] ?? 1;
    $ArchivoActual = $Archivo; 
        if($Tipo == 'archivo' && isset($_FILES["archivo"]["name"]) && $_FILES["archivo"]["name"] != ''){
        $fecha = new DateTime();
        $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["archivo"]["name"];
        $tmp_archivo = $_FILES["archivo"]['tmp_name'];
        $carpeta_archivos = "./Archivos/";
        if(!file_exists($carpeta_archivos)) mkdir($carpeta_archivos, 0755, true);
        if(move_uploaded_file($tmp_archivo, $carpeta_archivos . $nombre_archivo)){
            if(!empty($ArchivoActual) && file_exists($carpeta_archivos . $ArchivoActual)){
                unlink($carpeta_archivos . $ArchivoActual);
            }
            $Archivo = $nombre_archivo;
        } else {
            $Archivo = $ArchivoActual;
        }
    } elseif($Tipo == 'video' && isset($_FILES["video"]["name"]) && $_FILES["video"]["name"] != ''){
        $fecha = new DateTime();
        $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["video"]["name"];
        $tmp_video = $_FILES["video"]['tmp_name'];
        $carpeta_videos = "./Video/";
        if(!file_exists($carpeta_videos)) mkdir($carpeta_videos, 0755, true);
        if(move_uploaded_file($tmp_video, $carpeta_videos . $nombre_archivo)){
            if(!empty($ArchivoActual) && file_exists($carpeta_videos . $ArchivoActual)){
                unlink($carpeta_videos . $ArchivoActual);
            }
            $Archivo = $nombre_archivo;
        } else {
            $Archivo = $ArchivoActual;
        }
    } elseif($Tipo == 'enlace'){
        $Archivo = $_POST["Archivo"] ?? '';
        if(empty($Archivo)){
            $Archivo = $ArchivoActual;
        }
    } else {
        $Archivo = $ArchivoActual;
    }

    $sentencia = $conexion->prepare(
        "UPDATE Contenido SET
        Titulo = :Titulo,
        Tipo = :Tipo,
        Archivo = :Archivo,
        Bloqueado = :Bloqueado
        WHERE ID = :id");
    $sentencia->bindParam(":Titulo", $Titulo);
    $sentencia->bindParam(":Tipo", $Tipo);
    $sentencia->bindParam(":Archivo", $Archivo);
    $sentencia->bindParam(":Bloqueado", $Bloqueado);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    
    $mensaje = "Contenido actualizado correctamente";
    header("Location: contenido.php?id=" . $IDCurso . "&mensaje=" . urlencode($mensaje));
    exit;
}
?>
<?php include("../header.php") ?>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Editar Contenido</h2>
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del contenido</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ID" value="<?= $txtID ?>">
                        <input type="hidden" name="IDCurso" value="<?= $IDCurso ?>">

                        <div class="mb-4">
                            <label for="Titulo" class="form-label fw-bold"><i class="bi bi-card-heading"></i> Título:</label>
                            <input type="text" value="<?= htmlspecialchars($Titulo) ?>" 
                                   class="form-control form-control-lg border-2" 
                                   name="Titulo" id="Titulo" required/>
                            <small class="form-text text-muted d-block mt-2">Ingrese el título del contenido</small>
                        </div>

                        <div class="mb-4">
                            <label for="Tipo" class="form-label fw-bold"><i class="bi bi-collection"></i> Tipo de contenido:</label>
                            <select class="form-select form-select-lg border-2" 
                                    name="Tipo" id="Tipo" onchange="mostrarCampoAdecuado()" required>
                                <option value="" <?= ($Tipo == '') ? 'selected' : '' ?>>Seleccione un tipo</option>
                                <option value="video" <?= ($Tipo == 'video') ? 'selected' : '' ?>>Video</option>
                                <option value="archivo" <?= ($Tipo == 'archivo') ? 'selected' : '' ?>>Archivo</option>
                                <option value="enlace" <?= ($Tipo == 'enlace') ? 'selected' : '' ?>>Enlace</option>
                            </select>
                        </div>

                        <div id="campoVideo" class="mb-4" style="display: none;">
                            <label for="video" class="form-label fw-bold"><i class="bi bi-camera-video"></i> Subir video:</label>
                            <input type="file" class="form-control form-control-lg" name="video" id="video" accept="video/*">
                            <small class="form-text text-muted d-block mt-2">Deje en blanco para mantener el video actual. Formatos: MP4, WebM, OGG</small>
                            <?php if($Tipo == 'video' && !empty($Archivo)): ?>
                                <div class="mt-2">
                                    <span>Video actual: </span>
                                    <a href="Video/<?= htmlspecialchars($Archivo) ?>" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-play-circle"></i> <?= htmlspecialchars($Archivo) ?>
                                    </a>
                                    <div class="form-text"><i class="bi bi-info-circle"></i> Se mantendrá si no selecciona uno nuevo.</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="campoArchivo" class="mb-4" style="display: none;">
                            <label for="archivo" class="form-label fw-bold"><i class="bi bi-file-earmark-arrow-up"></i> Subir archivo:</label>
                            <input type="file" class="form-control form-control-lg" name="archivo" id="archivo">
                            <small class="form-text text-muted d-block mt-2">Deje en blanco para mantener el archivo actual.</small>
                            <?php if($Tipo == 'archivo' && !empty($Archivo)): ?>
                                <div class="mt-2">
                                    <span>Archivo actual: </span>
                                    <a href="Archivos/<?= htmlspecialchars($Archivo) ?>" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-download"></i> <?= htmlspecialchars($Archivo) ?>
                                    </a>
                                    <div class="form-text"><i class="bi bi-info-circle"></i> Se mantendrá si no selecciona uno nuevo.</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="campoEnlace" class="mb-4" style="display: none;">
                            <label for="Archivo" class="form-label fw-bold"><i class="bi bi-link-45deg"></i> URL:</label>
                            <input type="url" value="<?= ($Tipo == 'enlace') ? htmlspecialchars($Archivo) : '' ?>" 
                                   class="form-control form-control-lg border-2" 
                                   name="Archivo" id="Archivo" placeholder="https://ejemplo.com">
                            <small class="form-text text-muted d-block mt-2">Ingrese la URL completa (incluyendo http:// o https://)</small>
                        </div>

                        <div class="mb-4">
                            <label for="Bloqueado" class="form-label fw-bold"><i class="bi bi-lock"></i> Bloqueado:</label>
                            <select name="Bloqueado" id="Bloqueado" class="form-select" required>
                                <option value="1" <?= ($Bloqueado == 1) ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($Bloqueado == 0) ? 'selected' : '' ?>>No</option>
                            </select>
                            <small class="form-text text-muted d-block mt-2">Indica si el contenido está bloqueado para usuarios sin el plan adecuado</small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="contenido.php?id=<?= $IDCurso ?>" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Actualizar</button>
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

<?php include("../footer.php") ?>