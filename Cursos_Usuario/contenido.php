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
if(isset($_GET['txtID'])){
    $txtID = (int)$_GET['txtID'];
    $sentencia = $conexion->prepare("SELECT Archivo, Tipo FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    if($registro){
        $archivo = $registro['Archivo'];
        $tipo = $registro['Tipo'];
        if($tipo == 'video' && !empty($archivo) && file_exists("../Contenido/Video/".$archivo)) {
            unlink("../Contenido/Video/".$archivo);
        } elseif($tipo == 'archivo' && !empty($archivo) && file_exists("../Contenido/Archivos/".$archivo)) {
            unlink("../Contenido/Archivos/".$archivo);
        }
    }
    $sentencia = $conexion->prepare("DELETE FROM Contenido WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    header("Location: contenido.php?id=$id_curso&mensaje=Contenido eliminado");
    exit;
}
$sql = "SELECT 1 FROM Inscripciones 
        WHERE IDUsuario = ? AND IDCurso = ? AND Estado = 1 
        LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $id_curso]);
if ($stmt->rowCount() === 0) {
    die("No tienes acceso a este curso");
}
$sql = "SELECT ID, Nombre, Descripcion, Imagen, Precio, IDUsuario FROM Cursos WHERE ID = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_curso]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$curso) {
    die("Curso no encontrado");
}
$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);
$esProfesor = ($curso['IDUsuario'] == $_SESSION['user_id']);
$puedeEditar = $esAdmin || $esProfesor;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $puedeEditar) {
    if (isset($_POST['editar_curso'])) {
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
    if (isset($_POST['crear_contenido'])) {
        $titulo = trim($_POST['titulo_contenido'] ?? '');
        $tipo = $_POST['tipo_contenido'] ?? '';
        $bloqueado = isset($_POST['bloqueado_contenido']) ? intval($_POST['bloqueado_contenido']) : 1;
        $archivo = '';
        if ($tipo == 'archivo') {
            if (isset($_FILES["archivo_contenido"]["name"]) && $_FILES["archivo_contenido"]["name"] != '') {
                $fecha = new DateTime();
                $nombre_archivo = $fecha->getTimestamp() . "_" . $_FILES["archivo_contenido"]["name"];
                $tmp_archivo = $_FILES["archivo_contenido"]['tmp_name'];
                $carpeta_archivos = "../Contenido/Archivos/";
                if (!file_exists($carpeta_archivos)) mkdir($carpeta_archivos, 0755, true);
                if (move_uploaded_file($tmp_archivo, $carpeta_archivos . $nombre_archivo)) {
                    $archivo = $nombre_archivo;
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
                    $archivo = $nombre_archivo;
                }
            }
        } elseif ($tipo == 'enlace') {
            $archivo = trim($_POST['archivo_contenido'] ?? '');
        }

        if (empty($titulo) || empty($tipo) || empty($archivo)) {
            $mensaje_error = "Todos los campos obligatorios deben ser completados.";
        } else {
            $sql = "INSERT INTO Contenido (IDCurso, Titulo, Tipo, Archivo, Bloqueado) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id_curso, $titulo, $tipo, $archivo, $bloqueado]);
            header("Location: contenido.php?id=$id_curso&mensaje=Contenido creado");
            exit;
        }
    }
    if (isset($_POST['editar_contenido'])) {
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
            $sql = "UPDATE Contenido SET 
                        Titulo = ?, 
                        Tipo = ?, 
                        Archivo = ?, 
                        Bloqueado = ? 
                    WHERE ID = ? AND IDCurso = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$titulo, $tipo, $archivo, $bloqueado, $id_contenido, $id_curso]);
            header("Location: contenido.php?id=$id_curso&mensaje=Contenido actualizado");
            exit;
        }
    }
}

$sql = "SELECT * FROM Contenido WHERE IDCurso = ? ORDER BY ID ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_curso]);
$contenidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
function obtenerRutaArchivo($tipo, $nombreArchivo) {
    $directorioBase = '../Contenido/';
    $subdirectorio = ($tipo === 'video') ? 'Video/' : 'Archivos/';
    $rutaCompleta = $directorioBase . $subdirectorio . $nombreArchivo;
    if (file_exists($rutaCompleta)) return $rutaCompleta;
    return $nombreArchivo;
}

function obtenerTipoMIME($archivo) {
    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    $tipos = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'txt'  => 'text/plain',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed'
    ];
    return $tipos[$extension] ?? 'application/octet-stream';
}

function formatFileSize($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
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
                <!-- Imagen del curso -->
                <?php if (!empty($curso['Imagen'])): ?>
                    <div class="text-center mb-4">
                        <img src="../Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                             class="img-fluid rounded" 
                             alt="<?= htmlspecialchars($curso['Nombre']) ?>"
                             style="max-height: 300px;">
                    </div>
                <?php endif; ?>

                <!-- Precio -->
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
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Contenido del curso:</h5>
                    <?php if ($puedeEditar): ?>
                        <button type="button" class="btn btn-success btn-sm" id="btnMostrarAgregar" onclick="mostrarFormularioAgregar()">
                            <i class="bi bi-plus-circle"></i> Agregar contenido
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($puedeEditar): ?>
                    <div id="formAgregarContenido" style="display: none;" class="mt-4 p-3 border rounded bg-light">
                        <h6 class="mb-3"><i class="bi bi-plus-circle"></i> Agregar nuevo contenido</h6>
                        <form method="post" enctype="multipart/form-data" action="">
                            <input type="hidden" name="crear_contenido" value="1">
                            <input type="hidden" name="IDCurso" value="<?= $id_curso ?>">

                            <div class="row g-2 align-items-center">
                                <div class="">
                                    <label for="nuevoTitulo" class="form-label fw-bold mb-0">Título:</label>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="titulo_contenido" id="nuevoTitulo" required
                                           style="width:450px;">
                                </div>
                                <div class="">
                                    <label for="nuevoTipo" class="form-label fw-bold mb-0">Tipo:</label>
                                    <select class="form-select form-select-sm" 
                                            name="tipo_contenido" id="nuevoTipo" required
                                            onchange="mostrarCampoArchivoNuevo()"
                                            style="width:120px;">
                                        <option value="video">Video</option>
                                        <option value="archivo">Archivo</option>
                                        <option value="enlace">Enlace</option>
                                    </select>
                                </div>
                                <div class="" id="campoArchivoNuevo">
                                    <label for="nuevoArchivoFile" class="form-label fw-bold mb-0 d-none">Archivo:</label>
                                    <input type="file" class="form-control form-control-sm" 
                                           name="archivo_contenido" id="nuevoArchivoFile" 
                                           style="width:400px;">
                                    <input type="text" class="form-control form-control-sm d-none" 
                                           name="archivo_contenido" id="nuevoArchivoText" 
                                           placeholder="https://ejemplo.com"
                                           style="width:400px;">
                                </div>
                                <div class="">
                                    <label for="nuevoBloqueado" class="form-label fw-bold mb-0">Bloqueado:</label>
                                    <select class="form-select form-select-sm" 
                                            name="bloqueado_contenido" id="nuevoBloqueado"
                                            style="width:100px;">
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-save"></i> Guardar
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="ocultarFormularioAgregar()">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (empty($contenidos)): ?>
                    <div class="alert alert-info">Este curso aún no tiene contenido disponible.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($contenidos as $contenido): 
                            $rutaArchivo = '';
                            $esVideoExterno = false;
                            if ($contenido['Tipo'] === 'video' || $contenido['Tipo'] === 'archivo') {
                                $rutaArchivo = obtenerRutaArchivo($contenido['Tipo'], $contenido['Archivo']);
                                $esVideoExterno = filter_var($rutaArchivo, FILTER_VALIDATE_URL) !== false;
                            }
                            $tipoTexto = '';
                            if ($contenido['Tipo'] == 'enlace') $tipoTexto = 'Enlace:';
                            elseif ($contenido['Tipo'] == 'archivo') $tipoTexto = 'Archivo:';
                            elseif ($contenido['Tipo'] == 'video') $tipoTexto = 'Video:';
                        ?>
                        <div class="list-group-item">
                            <div id="vista_<?= $contenido['ID'] ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold"><?= htmlspecialchars($contenido['Titulo']) ?></span>
                                    <div>
                                        <?php if ($puedeEditar): ?>
                                            <button type="button" class="btn btn-warning btn-sm me-1" onclick="habilitarEdicionContenido(<?= $contenido['ID'] ?>)">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </button>
                                            <a href="contenido.php?txtID=<?= $contenido['ID'] ?>&id=<?= $id_curso ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('¿Está seguro de eliminar este contenido?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <strong><?= $tipoTexto ?></strong> 
                                    <?php if ($contenido['Tipo'] == 'enlace'): ?>
                                        <a href="<?= htmlspecialchars($contenido['Archivo']) ?>" target="_blank"><?= htmlspecialchars($contenido['Archivo']) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($contenido['Archivo']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-2">
                                    <span class="fw-bold"><?= ($contenido['Bloqueado'] == 1) ? 'Bloqueado' : 'Desbloqueado' ?></span>
                                </div>
                            </div>

                            <div id="edicion_<?= $contenido['ID'] ?>" style="display:none;">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="editar_contenido" value="1">
                                    <input type="hidden" name="id_contenido" value="<?= $contenido['ID'] ?>">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Título:</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="titulo_contenido" 
                                               value="<?= htmlspecialchars($contenido['Titulo']) ?>" 
                                               id="inputTitulo_<?= $contenido['ID'] ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tipo:</label>
                                        <select class="form-select form-select-sm" 
                                                name="tipo_contenido" 
                                                id="selectTipo_<?= $contenido['ID'] ?>">
                                            <option value="video" <?= ($contenido['Tipo'] == 'video') ? 'selected' : '' ?>>Video</option>
                                            <option value="archivo" <?= ($contenido['Tipo'] == 'archivo') ? 'selected' : '' ?>>Archivo</option>
                                            <option value="enlace" <?= ($contenido['Tipo'] == 'enlace') ? 'selected' : '' ?>>Enlace</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Archivo:</label>
                                        <input type="file" class="form-control form-control-sm" 
                                               name="archivo_contenido" 
                                               id="inputFile_<?= $contenido['ID'] ?>" 
                                               accept="<?= ($contenido['Tipo'] == 'video') ? 'video/*' : '*/*' ?>"
                                               style="<?= ($contenido['Tipo'] == 'enlace') ? 'display:none;' : '' ?>">
                                        <input type="text" class="form-control form-control-sm" 
                                               name="archivo_contenido" 
                                               value="<?= ($contenido['Tipo'] == 'enlace') ? htmlspecialchars($contenido['Archivo']) : '' ?>" 
                                               id="inputText_<?= $contenido['ID'] ?>" 
                                               placeholder="https://ejemplo.com"
                                               style="<?= ($contenido['Tipo'] == 'enlace') ? '' : 'display:none;' ?>">
                                        <small class="text-muted">Deje en blanco para mantener el archivo actual.</small>
                                    </div>

                                    <!-- Bloqueado -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Bloqueado:</label>
                                        <select class="form-select form-select-sm" 
                                                name="bloqueado_contenido" 
                                                id="selectBloqueado_<?= $contenido['ID'] ?>">
                                            <option value="1" <?= ($contenido['Bloqueado'] == 1) ? 'selected' : '' ?>>Sí</option>
                                            <option value="0" <?= ($contenido['Bloqueado'] == 0) ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>

                                    <div>
                                        <button type="submit" class="btn btn-success btn-sm">Guardar</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelarEdicionContenido(<?= $contenido['ID'] ?>)">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                            <?php if ($contenido['Tipo'] === 'video'): ?>
                                <?php if (strpos($rutaArchivo, 'youtube.com') !== false || strpos($rutaArchivo, 'youtu.be') !== false): 
                                    $video_id = '';
                                    if (preg_match('%(?:youtube(?:\.com|\.be)/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $rutaArchivo, $match)) {
                                        $video_id = $match[1];
                                    }
                                    $embed_url = "https://www.youtube.com/embed/{$video_id}?rel=0&showinfo=0";
                                ?>
                                    <div class="video-container my-3">
                                        <iframe src="<?= htmlspecialchars($embed_url) ?>" 
                                                title="<?= htmlspecialchars($contenido['Titulo'] ?? 'Video de YouTube') ?>" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen
                                                loading="lazy">
                                        </iframe>
                                    </div>
                                <?php else: 
                                    $esVideoValido = in_array(strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg']);
                                ?>
                                    <?php if ($esVideoValido && !$esVideoExterno && file_exists($rutaArchivo)): ?>
                                        <div class="video-container my-3">
                                            <video controls class="w-100">
                                                <source src="<?= htmlspecialchars($rutaArchivo) ?>" 
                                                        type="video/<?= pathinfo($rutaArchivo, PATHINFO_EXTENSION) ?>">
                                                Tu navegador no soporta la reproducción de video.
                                            </video>
                                        </div>
                                    <?php elseif ($esVideoExterno): ?>
                                        <div class="alert alert-warning mt-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            El video está alojado externamente. 
                                            <a href="<?= htmlspecialchars($rutaArchivo) ?>" target="_blank" class="alert-link">Ver video externo</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger mt-2">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            No se pudo cargar el video. El archivo no existe o el formato no es compatible.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            
                            <?php elseif ($contenido['Tipo'] === 'archivo'): 
                                $nombreArchivo = basename($rutaArchivo);
                                $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                                $tamanoArchivo = file_exists($rutaArchivo) ? filesize($rutaArchivo) : 0;
                                $tipoMIME = obtenerTipoMIME($nombreArchivo);
                                $iconos = [
                                    'pdf'  => 'fa-file-pdf text-danger',
                                    'doc'  => 'fa-file-word text-primary',
                                    'docx' => 'fa-file-word text-primary',
                                    'xls'  => 'fa-file-excel text-success',
                                    'xlsx' => 'fa-file-excel text-success',
                                    'ppt'  => 'fa-file-powerpoint text-warning',
                                    'pptx' => 'fa-file-powerpoint text-warning',
                                    'jpg'  => 'fa-file-image text-info',
                                    'jpeg' => 'fa-file-image text-info',
                                    'png'  => 'fa-file-image text-info',
                                    'gif'  => 'fa-file-image text-info',
                                    'txt'  => 'fa-file-alt text-secondary',
                                    'zip'  => 'fa-file-archive text-dark',
                                    'rar'  => 'fa-file-archive text-dark'
                                ];
                                $icono = $iconos[$extension] ?? 'fa-file text-muted';
                            ?>
                                <div class="file-download mt-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <i class="fas <?= $icono ?> fa-2x me-3"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= htmlspecialchars($contenido['Titulo']) ?></div>
                                            <div class="text-muted small">
                                                <?= strtoupper($extension) ?> • 
                                                <?= $tamanoArchivo > 0 ? formatFileSize($tamanoArchivo) : 'Tamaño desconocido' ?>
                                            </div>
                                        </div>
                                        <?php if (file_exists($rutaArchivo) && !$esVideoExterno): ?>
                                            <a href="<?= htmlspecialchars($rutaArchivo) ?>" 
                                               class="btn btn-sm btn-success" 
                                               download="<?= htmlspecialchars($nombreArchivo) ?>"
                                               target="_blank">
                                                <i class="fas fa-download me-1"></i> Descargar
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($rutaArchivo) ?>" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i> Abrir enlace
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            
                            <?php elseif ($contenido['Tipo'] === 'enlace'): 
                                $esYoutube = (strpos($contenido['Archivo'], 'youtube.com') !== false || strpos($contenido['Archivo'], 'youtu.be') !== false);
                                if ($esYoutube): 
                                    $video_id = '';
                                    if (preg_match('%(?:youtube(?:\.com|\.be)/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $contenido['Archivo'], $match)) {
                                        $video_id = $match[1];
                                    }
                                    $embed_url = "https://www.youtube.com/embed/{$video_id}";
                            ?>
                                    <div class="video-container my-3">
                                        <iframe src="<?= htmlspecialchars($embed_url) ?>" 
                                                title="<?= htmlspecialchars($contenido['Titulo'] ?? 'Video de YouTube') ?>" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen
                                                loading="lazy">
                                        </iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($contenido['Archivo']) ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> Abrir enlace
                                        </a>
                                        <small class="text-muted ms-2"><?= parse_url($contenido['Archivo'], PHP_URL_HOST) ?></small>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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

function habilitarEdicionContenido(id) {
    document.getElementById('vista_' + id).style.display = 'none';
    document.getElementById('edicion_' + id).style.display = 'block';
    const tipoSelect = document.getElementById('selectTipo_' + id);
    const tipoActual = tipoSelect.value;
    const fileInput = document.getElementById('inputFile_' + id);
    const textInput = document.getElementById('inputText_' + id);
    if (tipoActual === 'enlace') {
        fileInput.style.display = 'none';
        textInput.style.display = 'block';
    } else {
        textInput.style.display = 'none';
        fileInput.style.display = 'block';
    }
}

function cancelarEdicionContenido(id) {
    location.reload();
}

function mostrarFormularioAgregar() {
    document.getElementById('formAgregarContenido').style.display = 'block';
    document.getElementById('btnMostrarAgregar').style.display = 'none';
}

function ocultarFormularioAgregar() {
    document.getElementById('formAgregarContenido').style.display = 'none';
    document.getElementById('btnMostrarAgregar').style.display = 'inline-block';
}

function mostrarCampoArchivoNuevo() {
    const tipo = document.getElementById('nuevoTipo').value;
    const fileInput = document.getElementById('nuevoArchivoFile');
    const textInput = document.getElementById('nuevoArchivoText');
    const label = document.querySelector('#campoArchivoNuevo label');

    if (tipo === 'enlace') {
        fileInput.classList.add('d-none');
        fileInput.disabled = true;
        textInput.classList.remove('d-none');
        textInput.disabled = false;
        label.textContent = 'URL:';
    } else {
        textInput.classList.add('d-none');
        textInput.disabled = true;
        fileInput.classList.remove('d-none');
        fileInput.disabled = false;
        label.textContent = 'Archivo:';
        if (tipo === 'video') {
            fileInput.accept = 'video/*';
        } else {
            fileInput.accept = '*/*';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    mostrarCampoArchivoNuevo();
    document.querySelectorAll('select[id^="selectTipo_"]').forEach(function(select) {
        const id = select.id.replace('selectTipo_', '');
        select.addEventListener('change', function() {
            const tipo = this.value;
            const fileInput = document.getElementById('inputFile_' + id);
            const textInput = document.getElementById('inputText_' + id);
            if (tipo === 'enlace') {
                fileInput.style.display = 'none';
                textInput.style.display = 'block';
            } else {
                textInput.style.display = 'none';
                fileInput.style.display = 'block';
            }
        });
    });
});
</script>

<style>
    .list-group-item {
        margin-bottom: 1.5rem;
        border-radius: 0.5rem !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        padding: 1.5rem;
        transition: all 0.2s ease;
    }
    .list-group-item:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    .list-group-item h5 {
        color: #2c3e50;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }
    .video-container {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        background: #000;
        border-radius: 0.5rem;
        margin: 1.5rem 0;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    .video-container iframe,
    .video-container video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    .file-download {
        transition: all 0.2s ease;
        border-left: 4px solid #0d6efd;
    }
    .file-download:hover {
        background-color: #f8f9fa !important;
        transform: translateX(5px);
    }
    .btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        min-width: 120px;
        text-align: center;
    }
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        font-weight: 600;
    }
    @media (max-width: 768px) {
        .list-group-item {
            padding: 1rem;
        }
        .video-container {
            margin: 1rem 0;
            border-radius: 0.25rem;
        }
    }

    input:disabled, select:disabled, textarea:disabled {
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        color: inherit !important;
        font-weight: inherit !important;
        cursor: default;
        box-shadow: none !important;
        resize: none;
    }
    input:not(:disabled), select:not(:disabled), textarea:not(:disabled) {
        border: 1px solid #ced4da !important;
        background: #fff !important;
        padding: 0.375rem 0.75rem !important;
        color: #212529 !important;
        font-weight: normal !important;
    }
    textarea:disabled {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
    }
</style>

<?php include("../footer.php"); ?>