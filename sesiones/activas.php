<?php
include("../autenticacion.php");
include("../bd.php");

// Solo el admin (opcional, descomentar si se requiere)
// if ($_SESSION['rol'] != 1) {
//     header("Location: ../dashboard.php");
//     exit;
// }

// Obtener solo sesiones activas (Estado = 1)
$sentencia = $conexion->prepare("SELECT * FROM SesionesActivas WHERE Estado = 1");
$sentencia->execute();
$lista_sesiones = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../header.php"); ?>
<br>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_sesiones as $sesion) { ?>
                <tr>
                    <td><?php echo $sesion['ID']; ?></td>
                    <td><?php echo $sesion['IDUsuario']; ?></td>
                    <td><small><?php echo substr($sesion['TokenSesion'], 0, 20); ?>...</small></td>
                    <td><?php echo $sesion['Dispositivo'] ?? 'N/A'; ?></td>
                    <td><?php echo $sesion['FechaInicio']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>