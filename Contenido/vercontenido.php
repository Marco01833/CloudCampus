<?php
include("../bd.php");
include("../autenticacion.php");
include("../header.php");
$txtID = isset($_GET['txtID']) ? (int)$_GET['txtID'] : 0;
$id_curso = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($txtID > 0) {
    $sql = "SELECT c.*, cu.IDUsuario 
            FROM Contenido c
            LEFT JOIN Cursos cu ON c.IDCurso = cu.ID
            WHERE c.ID = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$txtID]);
    $contenido_unico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contenido_unico) {
        $contenidos = [$contenido_unico];
        $id_curso = $contenido_unico['IDCurso']; 
    } else {
        $contenidos = [];
    }
} else {
    if ($id_curso > 0) {
        $sql = "SELECT * FROM Contenido WHERE IDCurso = ? ORDER BY ID ASC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id_curso]);
        $contenidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $contenidos = [];
    }
}

$rol_usuario = $_SESSION['rol'] ?? 0;
$esAdmin = ($rol_usuario == 2);

if ($txtID > 0 && isset($contenido_unico) && $contenido_unico) {
    $esProfesor = ($contenido_unico['IDUsuario'] == $_SESSION['user_id']);
} else if ($id_curso > 0) {
    $sql_curso = "SELECT IDUsuario FROM cursos WHERE ID = ?";
    $stmt_curso = $conexion->prepare($sql_curso);
    $stmt_curso->execute([$id_curso]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);
    $esProfesor = ($curso && $curso['IDUsuario'] == $_SESSION['user_id']);
} else {
    $esProfesor = false;
}
$puedeEditar = $esAdmin || $esProfesor;

function obtenerRutaArchivo($tipo, $nombreArchivo) {
    $directorioBase = '../Cursos_Usuario/';
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
$sql = "SELECT * FROM Contenido WHERE IDCurso = ? ORDER BY ID ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_curso]);
$contenidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Contenido del curso:</h5>
        <?php if ($puedeEditar): ?>
            <a href="agregarcontenido.php?id=<?= $id_curso ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Agregar contenido
            </a>
        <?php endif; ?>
    </div>
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
                                <a href="../Contenido/editar.php?id=<?= $contenido['ID'] ?>&id_curso=<?= $id_curso ?>" class="btn btn-warning btn-sm me-1">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
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