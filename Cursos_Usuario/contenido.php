<?php
session_start();
include("../bd.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$id_curso = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_curso <= 0) {
    header("Location: ../Cursos/index.php");
    exit;
}

$sql = "SELECT ID, Nombre, Descripcion, Imagen, Precio, IDUsuario FROM Cursos WHERE ID = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_curso]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$curso) {
    die("Curso no encontrado");
}
$acceso = false;
$sql = "SELECT 1 FROM Inscripciones WHERE IDUsuario = ? AND IDCurso = ? AND Estado = 1 LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $id_curso]);
if ($stmt->rowCount() > 0) $acceso = true;
if (!$acceso && $curso['IDUsuario'] == $_SESSION['user_id']) $acceso = true;
if (!$acceso && $_SESSION['rol'] == 2) $acceso = true;
if (!$acceso) die("No tienes acceso a este curso");
$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($curso['IDUsuario'] == $_SESSION['user_id']);
$puedeEditar = $esAdmin || $esProfesor;
if (isset($_GET['txtID'])) {
    $txtID = (int)$_GET['txtID'];
    $sentencia = $conexion->prepare("SELECT Archivo, Tipo FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    if ($registro) {
        $archivo = $registro['Archivo'];
        $tipo = $registro['Tipo'];
        if ($tipo == 'video' && !empty($archivo) && file_exists("../Contenido/Video/".$archivo)) {
            unlink("../Contenido/Video/".$archivo);
        } elseif ($tipo == 'archivo' && !empty($archivo) && file_exists("../Contenido/Archivos/".$archivo)) {
            unlink("../Contenido/Archivos/".$archivo);
        }
    }
    $sentencia = $conexion->prepare("DELETE FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location: contenido.php?id=$id_curso&mensaje=Contenido eliminado");
    exit;
}if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_contenido'])) {
    $id_contenido = intval($_POST['id_contenido'] ?? 0);
    $titulo = trim($_POST['titulo_contenido'] ?? '');
    $tipo = $_POST['tipo_contenido'] ?? '';
    $bloqueado = isset($_POST['bloqueado_contenido']) ? intval($_POST['bloqueado_contenido']) : 1;
    $sentencia = $conexion->prepare("SELECT Archivo, Tipo FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $id_contenido);
    $sentencia->execute();
    $datos_actuales = $sentencia->fetch(PDO::FETCH_LAZY);
    $archivo_actual = $datos_actuales['Archivo'] ?? '';
    $tipo_anterior = $datos_actuales['Tipo'] ?? '';
    $archivo = $archivo_actual;
    if ($tipo != $tipo_anterior && ($tipo_anterior == 'video' || $tipo_anterior == 'archivo') && !empty($archivo_actual)) {
        $ruta_anterior = "../Contenido/" . ($tipo_anterior == 'video' ? 'Video/' : 'Archivos/') . $archivo_actual;
        if (file_exists($ruta_anterior)) unlink($ruta_anterior);
        $archivo = '';
    }

    if ($tipo == 'archivo') {
        if (isset($_FILES["archivo_contenido"]["name"]) && $_FILES["archivo_contenido"]["name"] != '') {
            $fecha = new DateTime();
            $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["archivo_contenido"]["name"];
            $tmp_archivo = $_FILES["archivo_contenido"]['tmp_name'];
            $carpeta_archivos = "../Contenido/Archivos/";
            if (!file_exists($carpeta_archivos)) mkdir($carpeta_archivos, 0755, true);
            if (move_uploaded_file($tmp_archivo, $carpeta_archivos . $nombre_archivo)) {
                if (!empty($archivo_actual) && $archivo_actual != $nombre_archivo && file_exists($carpeta_archivos . $archivo_actual)) {
                    unlink($carpeta_archivos . $archivo_actual);
                }
                $archivo = $nombre_archivo;
            }
        } else {
            if ($tipo_anterior == 'archivo' && !empty($archivo_actual)) {
                $archivo = $archivo_actual;
            } else {
                $archivo = '';
            }
        }
    } elseif ($tipo == 'video') {
        if (isset($_FILES["archivo_contenido"]["name"]) && $_FILES["archivo_contenido"]["name"] != '') {
            $fecha = new DateTime();
            $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["archivo_contenido"]["name"];
            $tmp_video = $_FILES["archivo_contenido"]['tmp_name'];
            $carpeta_videos = "../Contenido/Video/";
            if (!file_exists($carpeta_videos)) mkdir($carpeta_videos, 0755, true);
            if (move_uploaded_file($tmp_video, $carpeta_videos . $nombre_archivo)) {
                if (!empty($archivo_actual) && $archivo_actual != $nombre_archivo && file_exists($carpeta_videos . $archivo_actual)) {
                    unlink($carpeta_videos . $archivo_actual);
                }
                $archivo = $nombre_archivo;
            }
        } else {
            if ($tipo_anterior == 'video' && !empty($archivo_actual)) {
                $archivo = $archivo_actual;
            } else {
                $archivo = '';
            }
        }
    } elseif ($tipo == 'enlace') {
        $nuevo_enlace = (isset($_POST['archivo_contenido'])) ? trim($_POST['archivo_contenido']) : '';
        if (!empty($nuevo_enlace)) {
            if (($tipo_anterior == 'video' || $tipo_anterior == 'archivo') && !empty($archivo_actual)) {
                $ruta_anterior = "../Contenido/" . ($tipo_anterior == 'video' ? 'Video/' : 'Archivos/') . $archivo_actual;
                if (file_exists($ruta_anterior)) unlink($ruta_anterior);
            }
            $archivo = $nuevo_enlace;
        } else {
            if ($tipo_anterior == 'enlace' && !empty($archivo_actual)) {
                $archivo = $archivo_actual;
            } else {
                $archivo = '';
            }
        }
    }
    if (empty($titulo) || empty($tipo) || empty($archivo)) {
        $mensaje_error = "Todos los campos obligatorios deben ser completados (asegúrese de subir un archivo o video si corresponde).";
    } else {
        $sql = "UPDATE Contenido SET Titulo = ?, Tipo = ?, Archivo = ?, Bloqueado = ? WHERE ID = ? AND IDCurso = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$titulo, $tipo, $archivo, $bloqueado, $id_contenido, $id_curso]);
        header("Location: contenido.php?id=$id_curso&mensaje=Contenido actualizado");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_curso'])) {
    $nombre = trim($_POST['nombre_curso'] ?? '');
    $descripcion = trim($_POST['descripcion_curso'] ?? '');
    $precio = floatval($_POST['precio_curso'] ?? 0);
    $imagen_actual = $curso['Imagen'];
    $nueva_imagen = $imagen_actual;
    if (isset($_FILES['imagen_curso']['name']) && $_FILES['imagen_curso']['name'] != '') {
        $fecha = new DateTime();
        $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES['imagen_curso']['name'];
        $tmp_imagen = $_FILES['imagen_curso']['tmp_name'];
        $carpeta = "../Cursos/Imagen/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        if (move_uploaded_file($tmp_imagen, $carpeta . $nombre_archivo)) {
            if (!empty($imagen_actual) && $imagen_actual != 'default.jpg' && file_exists($carpeta . $imagen_actual)) {
                unlink($carpeta . $imagen_actual);
            }
            $nueva_imagen = $nombre_archivo;
        }
    }
    $sql = "UPDATE Cursos SET Nombre = ?, Descripcion = ?, Precio = ?, Imagen = ? WHERE ID = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nombre, $descripcion, $precio, $nueva_imagen, $id_curso]);
    header("Location: contenido.php?id=$id_curso&mensaje=Curso actualizado");
    exit;
}
include("../header.php");
$mensaje = $_GET['mensaje'] ?? '';
?>
<div class="container mt-4">
    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($mensaje_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card">
        <form id="formCurso" method="post" enctype="multipart/form-data">
            <input type="hidden" name="editar_curso" value="1">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <?php if ($puedeEditar): ?>
                        <span id="textoNombreCurso"><?= htmlspecialchars($curso['Nombre']) ?></span>
                        <input type="text" class="form-control d-none" name="nombre_curso" 
                               value="<?= htmlspecialchars($curso['Nombre']) ?>" id="inputNombreCurso" disabled
                               style="display:inline-block; width:auto;">
                    <?php else: ?>
                        <?= htmlspecialchars($curso['Nombre']) ?>
                    <?php endif; ?>
                </h4>
                <?php if ($puedeEditar): ?>
                    <div>
                        <button type="button" class="btn btn-warning btn-sm" id="btnEditarCurso" onclick="habilitarEdicionCurso()">
                            <i class="bi bi-pencil-square"></i> Editar
                        </button>
                        <button type="submit" class="btn btn-success btn-sm d-none" id="btnGuardarCurso">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm d-none" id="btnCancelarCurso" onclick="cancelarEdicionCurso()">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($curso['Imagen'])): ?>
                    <div class="text-center mb-4">
                        <img src="../Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                             class="img-fluid rounded" 
                             alt="<?= htmlspecialchars($curso['Nombre']) ?>"
                             style="max-height: 300px;">
                    </div>
                <?php endif; ?>
                <div class="mb-2">
                    <h5>Precio:</h5>
                    <?php if ($puedeEditar): ?>
                        <span id="textoPrecioCurso">$<?= number_format($curso['Precio'], 2) ?></span>
                        <input type="number" step="0.01" class="form-control d-none" name="precio_curso" 
                               value="<?= $curso['Precio'] ?>" id="inputPrecioCurso" disabled
                               style="display:inline-block; width:auto;">
                    <?php else: ?>
                        <span>$<?= number_format($curso['Precio'], 2) ?></span>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <h5>Descripción del curso:</h5>
                    <?php if ($puedeEditar): ?>
                        <span id="textoDescripcionCurso"><?= nl2br(htmlspecialchars($curso['Descripcion'])) ?></span>
                        <textarea class="form-control d-none" name="descripcion_curso" 
                                  id="inputDescripcionCurso" disabled rows="3"><?= htmlspecialchars($curso['Descripcion']) ?></textarea>
                    <?php else: ?>
                        <p class="lead"><?= nl2br(htmlspecialchars($curso['Descripcion'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($puedeEditar): ?>
                    <div id="campoImagenCurso" class="mt-2 d-none">
                        <label for="inputImagenCurso" class="form-label fw-bold">Imagen:</label>
                        <span id="textoImagenCurso" class="d-block text-muted small">
                            <?= $curso['Imagen'] ? 'Imagen actual: '.htmlspecialchars($curso['Imagen']) : 'Sin imagen' ?>
                        </span>
                        <input type="file" class="form-control" name="imagen_curso" 
                               id="inputImagenCurso" accept="image/*" disabled>
                        <small class="text-muted">Deje en blanco para mantener la imagen actual.</small>
                    </div>
                <?php endif; ?>
            </div> 
        </form> 
        <div class="card-body">
            <?php include(__DIR__ . "/vercontenido.php"); ?>
        </div> 
    </div> 
</div>
<script>
function habilitarEdicionCurso() {
    document.getElementById('textoNombreCurso').classList.add('d-none');
    document.getElementById('inputNombreCurso').classList.remove('d-none');
    document.getElementById('inputNombreCurso').disabled = false;
    document.getElementById('textoPrecioCurso').classList.add('d-none');
    document.getElementById('inputPrecioCurso').classList.remove('d-none');
    document.getElementById('inputPrecioCurso').disabled = false;
    document.getElementById('textoDescripcionCurso').classList.add('d-none');
    document.getElementById('inputDescripcionCurso').classList.remove('d-none');
    document.getElementById('inputDescripcionCurso').disabled = false;
    document.getElementById('campoImagenCurso').classList.remove('d-none');
    document.getElementById('inputImagenCurso').disabled = false;
    document.getElementById('btnEditarCurso').classList.add('d-none');
    document.getElementById('btnGuardarCurso').classList.remove('d-none');
    document.getElementById('btnCancelarCurso').classList.remove('d-none');
}
function cancelarEdicionCurso() {
    location.reload();
}
</script>
<?php include("../footer.php"); ?>