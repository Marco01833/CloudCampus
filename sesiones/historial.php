<?php
include("../autenticacion.php");
include("../bd.php");
$id_usuario = $_SESSION['user_id'] ?? null;
if (!$id_usuario) {
    header("Location: ../login.php");
    exit;
}
$sentencia = $conexion->prepare("SELECT * FROM SesionesActivas 
                                  WHERE IDUsuario = :id_usuario AND Estado = 1 
                                  ORDER BY FechaInicio DESC");
$sentencia->execute([':id_usuario' => $id_usuario]);
$lista_sesiones = $sentencia->fetchAll(PDO::FETCH_ASSOC);
$mensaje = $_GET['mensaje'] ?? null;
?>
<?php include("../header.php"); ?>
<br>
<?php if($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<link rel="stylesheet" href="../css/historial.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// seguridad</span>
            <h1>Mis sesiones activas</h1>
            <p>Revisá los dispositivos con sesión abierta y cerralos si es necesario.</p>
        </div>
    </div>

    <?php if($mensaje): ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= htmlspecialchars($mensaje) ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-toolbar-title">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Sesiones activas</span>
            </div>
            <span class="table-count"><strong><?= count($lista_sesiones) ?></strong> sesiones encontradas</span>
        </div>

        <?php if(count($lista_sesiones) > 0): ?>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dispositivo</th>
                        <th>Fecha inicio</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_sesiones as $sesion): ?>
                    <tr>
                        <td class="cell-id">#<?= $sesion['ID'] ?></td>
                        <td><?= htmlspecialchars($sesion['Dispositivo'] ?? 'N/A') ?></td>
                        <td class="cell-muted"><?= $sesion['FechaInicio'] ?></td>
                        <td class="col-actions">
                            <?php if($sesion['ID'] != $_SESSION['session_id']): ?>
                                <a href="<?= $url_base ?>cerrar_sesion_id.php?id=<?= $sesion['ID'] ?>"
                                   class="icon-btn icon-btn-danger" title="Cerrar sesión"
                                   onclick="return confirm('¿Cerrar esta sesión?')">
                                    <i class="fa-solid fa-power-off"></i>
                                </a>
                            <?php else: ?>
                                <span class="badge badge-status status-on"><i class="fa-solid fa-circle-check"></i> Sesión actual</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de sesiones: <strong><?= count($lista_sesiones) ?></strong>
        </div>

        <?php else: ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No tienes sesiones activas en este momento.</p>
        </div>

        <?php endif; ?>

    </div>

</div>