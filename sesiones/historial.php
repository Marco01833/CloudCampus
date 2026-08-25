<?php
include("../autenticacion.php");
include("../bd.php");
$sentencia = $conexion->prepare("SELECT * FROM SesionesActivas WHERE Estado = 1 ORDER BY FechaInicio DESC");
$sentencia->execute();
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
<div class="card">
    <div class="card-header">
        <h4>Sesiones Activas</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>ID Usuario</th>
                    <th>Token</th>
                    <th>Dispositivo</th>
                    <th>Fecha Inicio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_sesiones as $sesion): ?>
                <tr>
                    <td><?= $sesion['ID'] ?></td>
                    <td><?= $sesion['IDUsuario'] ?></td>
                    <td><small><?= substr($sesion['TokenSesion'], 0, 15) ?>...</small></td>
                    <td><?= $sesion['Dispositivo'] ?? 'N/A' ?></td>
                    <td><?= $sesion['FechaInicio'] ?></td>
                    <td>
                        <?php if($sesion['ID'] != $_SESSION['session_id']): ?>
                            <a href="<?= $url_base ?>cerrar_sesion_id.php?id=<?= $sesion['ID'] ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Cerrar esta sesión?')">
                                <i class="bi bi-x-circle"></i> Cerrar
                            </a>
                        <?php else: ?>
                            <span class="badge bg-success">Sesión actual</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>