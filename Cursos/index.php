<?php
include("../bd.php");
include("../autenticacion.php");
require_once __DIR__ . '/../mail_config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $curso_id = (int)$_POST['curso_id'];
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'Pendiente';
    $motivo = $_POST['motivo'] ?? '';
    $id_administrador = $_SESSION['user_id'];
    $estados_validos = ['Pendiente', 'Aprobado', 'Rechazado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $stmt = $conexion->prepare("
            SELECT 
                c.ID, 
                c.Nombre as CursoNombre, 
                c.Descripcion, 
                c.Precio, 
                c.Imagen, 
                c.Estado,
                u.Correo,
                dp.Nombre as ProfesorNombre,
                dp.Apellidos as ProfesorApellidos
            FROM cursos c
            INNER JOIN Usuarios u ON c.IDUsuario = u.ID
            LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
            WHERE c.ID = ?
        ");
        $stmt->execute([$curso_id]);
        $curso_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($curso_data) {
            $estado_anterior = $curso_data['Estado'] ?? 'Pendiente';
            
            if ($nuevo_estado === 'Aprobado') {
                $stmt_contenido = $conexion->prepare("SELECT COUNT(*) as total FROM Contenido WHERE IDCurso = ?");
                $stmt_contenido->execute([$curso_id]);
                $resultado_contenido = $stmt_contenido->fetch(PDO::FETCH_ASSOC);
                $total_contenidos = $resultado_contenido['total'] ?? 0;
                
                $stmt_cuestionarios = $conexion->prepare("SELECT COUNT(*) as total FROM Cuestionarios WHERE IDCurso = ?");
                $stmt_cuestionarios->execute([$curso_id]);
                $resultado_cuestionarios = $stmt_cuestionarios->fetch(PDO::FETCH_ASSOC);
                $total_cuestionarios = $resultado_cuestionarios['total'] ?? 0;
                
                if ($total_contenidos < 4 || $total_cuestionarios < 2) {
                    $mensaje_error = "El curso debe tener mínimo 4 contenidos y 2 valoraciones. Actualmente tiene " . $total_contenidos . " contenidos y " . $total_cuestionarios . " valoraciones.";
                    header("Location: index.php?error=" . urlencode($mensaje_error));
                    exit;
                }
            }
            
            $stmt = $conexion->prepare("UPDATE cursos SET Estado = ? WHERE ID = ?");
            $stmt->execute([$nuevo_estado, $curso_id]);
            
            $stmt_auditoria = $conexion->prepare("
                INSERT INTO auditoria_cursos (IDCurso, IDAdministrador, EstadoAnterior, EstadoNuevo, Motivo, Fecha)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt_auditoria->execute([$curso_id, $id_administrador, $estado_anterior, $nuevo_estado, $motivo]);
            $mensaje = "Estado actualizado correctamente.";
            if (in_array($nuevo_estado, ['Aprobado', 'Rechazado'])) {
                try {
                    $mail = crearMailer();
                    $mail->addAddress($curso_data['Correo']);
                    $mail->isHTML(true);
                    $mail->Subject = "Actualización del estado de tu curso: " . $curso_data['CursoNombre'];
                    $nombre_completo = trim(($curso_data['ProfesorNombre'] ?? '') . ' ' . ($curso_data['ProfesorApellidos'] ?? ''));
                    if (empty($nombre_completo)) {
                        $nombre_completo = 'Profesor';
                    }
                    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    $base_url = $protocolo . "://" . $host;
                    $imagen_url = '';
                    if (!empty($curso_data['Imagen']) && file_exists("../Cursos_Usuario/Imagen/" . $curso_data['Imagen'])) {
                        $imagen_url = $base_url . "/Cursos_Usuario/Imagen/" . $curso_data['Imagen'];
                    } else {
                        $imagen_url = $base_url . "/Cursos_Usuario/Imagen/default.jpg";
                    }
                    $html = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
                            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
                            .header h1 { color: #2c3e50; margin: 0; font-size: 24px; }
                            .content { padding: 25px 0; }
                            .content p { color: #555; font-size: 16px; line-height: 1.6; }
                            .curso-detail { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                            .curso-detail .label { font-weight: bold; color: #2c3e50; }
                            .estado-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
                            .estado-aprobado { background: #d4edda; color: #155724; }
                            .estado-rechazado { background: #f8d7da; color: #721c24; }
                            .estado-pendiente { background: #fff3cd; color: #856404; }
                            .footer { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; color: #999; font-size: 12px; }
                            .img-curso { max-width: 100%; height: auto; border-radius: 5px; margin: 10px 0; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>📚 Cloud Campus</h1>
                                <p>Notificación de cambio de estado</p>
                            </div>
                            <div class="content">
                                <p>Hola <strong>' . htmlspecialchars($nombre_completo) . '</strong>,</p>
                                <p>El estado de tu curso <strong>"' . htmlspecialchars($curso_data['CursoNombre']) . '"</strong> ha sido actualizado.</p>
                                <div class="curso-detail">
                                    <p><span class="label">Profesor:</span> ' . htmlspecialchars($nombre_completo) . '</p>
                                    <p><span class="label">Correo:</span> ' . htmlspecialchars($curso_data['Correo']) . '</p>
                                    <p><span class="label">Título:</span> ' . htmlspecialchars($curso_data['CursoNombre']) . '</p>
                                    <p><span class="label">Descripción:</span> ' . htmlspecialchars($curso_data['Descripcion'] ?? 'Sin descripción') . '</p>
                                    <p><span class="label">Precio:</span> $' . number_format($curso_data['Precio'], 2) . '</p>
                                    <p><span class="label">Imagen:</span><br>
                                    <img src="' . htmlspecialchars($imagen_url) . '" alt="Imagen del curso" class="img-curso" style="max-width: 200px;"></p>
                                    <p><span class="label">Estado:</span> 
                                        <span class="estado-badge estado-' . strtolower($nuevo_estado) . '">' . htmlspecialchars($nuevo_estado) . '</span>
                                    </p>
                                </div>
                                <p>Si tienes preguntas, contacta al administrador.</p>
                            </div>
                            <div class="footer">
                                <p>© 2025 Cloud Campus - Todos los derechos reservados</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ';

                    $mail->Body = $html;
                    $mail->AltBody = "Hola " . $nombre_completo . ",\n\nEl estado de tu curso '" . $curso_data['CursoNombre'] . "' ha sido actualizado a: " . $nuevo_estado . "\n\nProfesor: " . $nombre_completo . "\nCorreo: " . $curso_data['Correo'] . "\nTítulo: " . $curso_data['CursoNombre'] . "\nDescripción: " . ($curso_data['Descripcion'] ?? 'Sin descripción') . "\nPrecio: $" . number_format($curso_data['Precio'], 2) . "\n\nCloud Campus";
                    
                    $mail->send();
                    
                } catch (Exception $e) {
                    $mensaje .= " (Correo no enviado: " . $e->getMessage() . ")";
                }
            }
        } else {
            $mensaje_error = "Curso no encontrado.";
        }
    } else {
        $mensaje_error = "Estado no válido.";
    }
    header("Location: index.php?mensaje=" . urlencode($mensaje ?? $mensaje_error));
    exit;
}
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$estados_validos_filtro = ['Pendiente', 'Aprobado', 'Rechazado', 'todos'];
if (!in_array($filtro_estado, $estados_validos_filtro)) {
    $filtro_estado = 'todos';
}

if ($filtro_estado == 'todos') {
    $sentencia = $conexion->prepare("
        SELECT c.*, u.Correo as UsuarioCorreo 
        FROM cursos c
        INNER JOIN Usuarios u ON c.IDUsuario = u.ID
        ORDER BY c.ID DESC
    ");
    $sentencia->execute();
} else {
    $sentencia = $conexion->prepare("
        SELECT c.*, u.Correo as UsuarioCorreo 
        FROM cursos c
        INNER JOIN Usuarios u ON c.IDUsuario = u.ID
        WHERE c.Estado = ?
        ORDER BY c.ID DESC
    ");
    $sentencia->execute([$filtro_estado]);
}
$lista_cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>


<link rel="stylesheet" href="../css/index-curso.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// panel</span>
            <h1>Gestión de Cursos</h1>
            <p>Administrá los cursos, revisá su estado y aprobá o rechazá las solicitudes.</p>
        </div>
        
    </div>

    <?php if(isset($_GET['mensaje'])): ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= htmlspecialchars($_GET['mensaje']) ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert-box danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars($_GET['error']) ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if(isset($mensaje_error)): ?>
        <div class="alert-box danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars($mensaje_error) ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscarCurso" placeholder="Buscar por título o profesor...">
            </div>
            <div class="table-toolbar-actions d-flex flex-wrap align-items-center gap-3 p-3 bg-light rounded-3 shadow-sm">
    <form method="get" action="" class="filter-form d-flex align-items-center gap-2 flex-wrap">
        <label for="estado" class="fw-semibold text-secondary mb-0">Filtrar por estado:</label>
        <select name="estado" id="estado" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="todos" <?= ($filtro_estado == 'todos') ? 'selected' : '' ?>>Todos</option>
            <option value="Pendiente" <?= ($filtro_estado == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
            <option value="Aprobado" <?= ($filtro_estado == 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
            <option value="Rechazado" <?= ($filtro_estado == 'Rechazado') ? 'selected' : '' ?>>Rechazado</option>
        </select>
        <noscript>
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </noscript>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </form>
    <span class="table-count ms-auto fw-light text-secondary">
        <span class="badge bg-primary rounded-pill me-1" id="conteoCursos"><?= count($lista_cursos) ?></span>
        cursos encontrados
    </span>
</div>
        </div>

        <?php if(count($lista_cursos) > 0): ?>

        <div class="table-scroll">
            <table class="data-table" id="tablaCursos">
                <thead>
                    <tr>
                        <th class="col-thumb"></th>
                        <th>Título</th>
                        <th>Profesor</th>
                        <th class="col-desc">Descripción</th>
                        <th class="col-price">Precio</th>
                        <th>Estado</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_cursos as $curso): 
                        $estado = $curso['Estado'] ?? 'Pendiente';
                        $estadoClase = strtolower($estado);
                    ?>
                    <tr>
                        <td class="col-thumb">
                            <?php if(!empty($curso['Imagen']) && file_exists("../Cursos_Usuario/Imagen/".$curso['Imagen'])): ?>
                                <img src="../Cursos_Usuario/Imagen/<?= $curso['Imagen'] ?>" class="dash-thumb" alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                            <?php else: ?>
                                <img src="../Cursos_Usuario/Imagen/default.jpg" class="dash-thumb" alt="default">
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div class="dash-table-title"><?= htmlspecialchars($curso['Nombre']) ?></div>
                        </td>
                        <td>
                            <span class="badge badge-role role-teacher">
                                <i class="fa-regular fa-id-badge"></i> <?= htmlspecialchars($curso['UsuarioCorreo']) ?>
                            </span>
                        </td>
                        <td class="col-desc"><?= htmlspecialchars(substr($curso['Descripcion'] ?? '', 0, 90)) ?><?= (strlen($curso['Descripcion'] ?? '') > 90) ? '...' : '' ?></td>
                        <td class="col-price"><span class="dash-price">$<?= number_format($curso['Precio'], 2) ?></span></td>
                        <td>
                        <span class="badge badge-status status-<?= $estadoClase ?> rounded-pill px-3 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-1 border border-2" style="background-color: #fff; color: #000;">
                        <i class="fas fa-circle me-1" style="font-size: 0.6rem; color: <?= ($estadoClase == 'activo' ? '#28a745' : '#dc3545') ?>;"></i>
                        <?= $estado ?></span></td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <form method="post" action="" class="status-form" id="form-<?= $curso['ID'] ?>">
                                    <input type="hidden" name="curso_id" value="<?= $curso['ID'] ?>">
                                    <input type="hidden" name="motivo" id="motivo-<?= $curso['ID'] ?>" value="">
                                    <select name="nuevo_estado" class="status-select" id="estado-<?= $curso['ID'] ?>">
                                        <option value="Pendiente" <?= ($estado == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="Aprobado" <?= ($estado == 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
                                        <option value="Rechazado" <?= ($estado == 'Rechazado') ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                    <button type="button" class="icon-btn icon-btn-primary btn-cambiar-estado" data-curso-id="<?= $curso['ID'] ?>" data-curso-nombre="<?= htmlspecialchars($curso['Nombre']) ?>" title="Actualizar estado">
                                        <i class="fa-solid fa-arrows-rotate"></i>
                                    </button>
                                </form>
                                <a class="icon-btn" href="../Cursos_Usuario/contenido.php?id=<?= $curso['ID'] ?>" title="Ver curso">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de cursos: <strong><?= count($lista_cursos) ?></strong>
        </div>

        <?php else: ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No hay cursos registrados todavía.</p>
        </div>

        <?php endif; ?>

    </div>

</div>

<div id="modalMotivo" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalMotivoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalMotivoLabel">
                    <i class="fa-solid fa-comment-dots"></i> Registrar Motivo del Cambio de Estado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="text-muted mb-3">
                        <strong>Curso:</strong> <span id="cursoNombreModal"></span>
                    </p>
                </div>
                <div id="alertaErrorValidacion" class="alert alert-danger d-none" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="mensajeErrorValidacion"></span>
                </div>
                <div id="contenidoMotivoDiv">
                    <div class="mb-3">
                        <label for="motivoInput" class="form-label fw-bold">
                            Motivo del cambio de estado <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="motivoInput" rows="4" placeholder="Escriba el motivo del cambio de estado del curso..." required></textarea>
                        <small class="text-muted d-block mt-2">Este motivo será registrado en el sistema de auditoría.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarMotivo">
                    <i class="fa-solid fa-check"></i> Confirmar Cambio
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let cursoActual = null;
    let modalMotivo = null;

    document.addEventListener('DOMContentLoaded', function() {
        modalMotivo = new bootstrap.Modal(document.getElementById('modalMotivo'), {
            backdrop: 'static',
            keyboard: false
        });

        const botonesEstado = document.querySelectorAll('.btn-cambiar-estado');
        botonesEstado.forEach(boton => {
            boton.addEventListener('click', function(e) {
                e.preventDefault();
                const cursoId = this.getAttribute('data-curso-id');
                const cursoNombre = this.getAttribute('data-curso-nombre');
                const selectEstado = document.getElementById('estado-' + cursoId);
                
                const estadoSeleccionado = selectEstado.value;
                const statusFormElement = document.getElementById('form-' + cursoId);
                
                cursoActual = {
                    id: cursoId,
                    nombre: cursoNombre,
                    nuevoEstado: estadoSeleccionado,
                    form: statusFormElement
                };
                
                document.getElementById('cursoNombreModal').textContent = cursoNombre;
                document.getElementById('motivoInput').value = '';
                
                document.getElementById('alertaErrorValidacion').classList.add('d-none');
                document.getElementById('contenidoMotivoDiv').style.display = 'block';
                document.getElementById('btnConfirmarMotivo').style.display = 'block';
                
                if (estadoSeleccionado === 'Aprobado') {
                    const formData = new FormData();
                    formData.append('curso_id', cursoId);
                    formData.append('nuevo_estado', estadoSeleccionado);
                    
                    fetch('./validar_curso.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.valido) {
                            document.getElementById('alertaErrorValidacion').classList.remove('d-none');
                            document.getElementById('mensajeErrorValidacion').textContent = data.mensaje;
                            document.getElementById('contenidoMotivoDiv').style.display = 'none';
                            document.getElementById('btnConfirmarMotivo').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error en la validación:', error);
                    });
                }
                
                modalMotivo.show();
            });
        });

        document.getElementById('btnConfirmarMotivo').addEventListener('click', function() {
            const motivo = document.getElementById('motivoInput').value.trim();
            
            if (!motivo) {
                alert('Por favor ingrese un motivo para el cambio de estado.');
                return;
            }

            if (cursoActual) {
                document.getElementById('motivo-' + cursoActual.id).value = motivo;
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'cambiar_estado';
                submitInput.value = '1';
                cursoActual.form.appendChild(submitInput);
                
                cursoActual.form.submit();
            }
        });
    });

    const inputBuscar = document.getElementById('buscarCurso');
    const tabla = document.getElementById('tablaCursos');

    if (inputBuscar && tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        const conteo = document.getElementById('conteoCursos');

        inputBuscar.addEventListener('input', () => {
            const texto = inputBuscar.value.trim().toLowerCase();
            let visibles = 0;

            filas.forEach(fila => {
                const titulo = fila.children[2].textContent.toLowerCase();
                const profesor = fila.children[3].textContent.toLowerCase();
                const coincide = titulo.includes(texto) || profesor.includes(texto);
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            if (conteo) conteo.textContent = visibles;
        });
    }
</script>
<?php include("../footer.php"); ?>