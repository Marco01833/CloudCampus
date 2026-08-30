<?php
include("../bd.php");
include("../autenticacion.php");
require_once __DIR__ . '/../mail_config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $curso_id = (int)$_POST['curso_id'];
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'Pendiente';
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
            $stmt = $conexion->prepare("UPDATE cursos SET Estado = ? WHERE ID = ?");
            $stmt->execute([$nuevo_estado, $curso_id]);
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
                                    <p><span class="label">👤 Profesor:</span> ' . htmlspecialchars($nombre_completo) . '</p>
                                    <p><span class="label">📧 Correo:</span> ' . htmlspecialchars($curso_data['Correo']) . '</p>
                                    <p><span class="label">📌 Título:</span> ' . htmlspecialchars($curso_data['CursoNombre']) . '</p>
                                    <p><span class="label">📝 Descripción:</span> ' . htmlspecialchars($curso_data['Descripcion'] ?? 'Sin descripción') . '</p>
                                    <p><span class="label">💰 Precio:</span> $' . number_format($curso_data['Precio'], 2) . '</p>
                                    <p><span class="label">🖼️ Imagen:</span><br>
                                    <img src="' . htmlspecialchars($imagen_url) . '" alt="Imagen del curso" class="img-curso" style="max-width: 200px;"></p>
                                    <p><span class="label">📊 Estado:</span> 
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

<?php if(isset($_GET['mensaje'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($mensaje_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($mensaje_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">Gestión de Cursos</h4>
        <form method="get" action="" class="d-flex align-items-center gap-2">
            <label for="estado" class="mb-0 me-1">Filtrar por estado:</label>
            <select name="estado" id="estado" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
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
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Profesor</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Cambiar Estado</th>
                        <th>Ver</th> <!-- Nueva columna -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_cursos as $curso): ?>
                    <tr>
                        <td><?= $curso['ID'] ?></td>
                        <td><?= htmlspecialchars($curso['UsuarioCorreo']) ?></td>
                        <td><?= htmlspecialchars($curso['Nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['Descripcion'] ?? '') ?></td>
                        <td>$<?= number_format($curso['Precio'], 2) ?></td>
                        <td>
                            <?php if(!empty($curso['Imagen']) && file_exists("../Cursos_Usuario/Imagen/".$curso['Imagen'])): ?>
                                <img src="../Cursos_Usuario/Imagen/<?= $curso['Imagen'] ?>" width="50" height="50" style="object-fit: cover;" class="rounded" alt="img">
                            <?php else: ?>
                                <img src="../Cursos_Usuario/Imagen/default.jpg" width="50" height="50" style="object-fit: cover;" class="rounded" alt="default">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $estado = $curso['Estado'] ?? 'Pendiente';
                            ?>
                            <span><?= $estado ?></span>
                        </td>
                        <td>
                            <form method="post" action="" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="curso_id" value="<?= $curso['ID'] ?>">
                                <select name="nuevo_estado" class="form-select form-select-sm" style="width: auto;">
                                    <option value="Pendiente" <?= ($estado == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="Aprobado" <?= ($estado == 'Aprobado') ? 'selected' : '' ?>>Aprobado</option>
                                    <option value="Rechazado" <?= ($estado == 'Rechazado') ? 'selected' : '' ?>>Rechazado</option>
                                </select>
                                <button type="submit" name="cambiar_estado" class="btn btn-primary btn-sm">Actualizar</button>
                            </form>
                        </td>
                        <td>
                            <a href="../Cursos_Usuario/contenido.php?id=<?= $curso['ID'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye"></i> Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">Total de cursos: <?= count($lista_cursos) ?></div>
</div>
<?php include("../footer.php"); ?>