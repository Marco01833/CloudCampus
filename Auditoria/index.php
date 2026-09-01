<?php
session_start();
include("../bd.php");
include("../autenticacion.php");
if ($_SESSION['rol'] != 2) {
    header("Location: ../index.php");
    exit;
}
$id_usuario = $_SESSION['user_id'];

$sql = "SELECT 
            a.IDCurso,
            a.IDAdministrador,
            a.EstadoAnterior,
            a.EstadoNuevo,
            a.Motivo,
            a.Fecha,
            u.Correo AS correo_admin,
            c.Nombre AS nombre_curso
        FROM auditoria_cursos a
        LEFT JOIN Usuarios u ON a.IDAdministrador = u.ID
        LEFT JOIN cursos c ON a.IDCurso = c.ID
        ORDER BY a.Fecha DESC";

$sentencia = $conexion->prepare($sql);
$sentencia->execute();
$registros_auditoria = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>
<link rel="stylesheet" href="../css/index-planes.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// auditoria</span>
            <h1>Auditoría de Cursos</h1>
            <p>Registra y visualiza todos los cambios de estado realizados en los cursos.</p>
        </div>
    </div>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscarAuditoria" placeholder="Buscar por nombre de curso o administrador...">
            </div>
            <span class="table-count"><strong id="conteoAuditoria"><?php echo count($registros_auditoria); ?></strong> registros encontrados</span>
        </div>

        <?php if(count($registros_auditoria) > 0) { ?>

        <div class="table-scroll">
            <table class="data-table" id="tablaAuditoria">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Administrador</th>
                        <th>Estado Anterior</th>
                        <th>Estado Nuevo</th>
                        <th>Motivo</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($registros_auditoria as $registro) { 
                        $clase_estado = '';
                        if ($registro['EstadoNuevo'] == 'Aprobado') {
                            $clase_estado = 'table-success';
                        } elseif ($registro['EstadoNuevo'] == 'Rechazado') {
                            $clase_estado = 'table-danger';
                        } else {
                            $clase_estado = 'table-warning';
                        }
                    ?>
                    <tr class="<?= $clase_estado ?>">
                        <td><div class="dash-table-title"><?php echo htmlspecialchars($registro['nombre_curso'] ?? 'Curso Eliminado'); ?><br><small class="cell-muted"></small></div></td>
                        <td><div class="dash-table-title"><?php echo htmlspecialchars($registro['correo_admin'] ?? 'Desconocido'); ?></div></td>
                        <td>
                            <?php 
                                $estado_anterior = $registro['EstadoAnterior'];
                                if ($estado_anterior == 'Aprobado') {
                                    echo '<span class="badge" style="background-color: #28a745; color: #fff;">Aprobado</span>';
                                } elseif ($estado_anterior == 'Rechazado') {
                                    echo '<span class="badge" style="background-color: #dc3545; color: #fff;">Rechazado</span>';
                                } else {
                                    echo '<span class="badge" style="background-color: #ffc107; color: #333;">Pendiente</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <?php 
                                $estado_nuevo = $registro['EstadoNuevo'];
                                if ($estado_nuevo == 'Aprobado') {
                                    echo '<span class="badge" style="background-color: #28a745; color: #fff;">Aprobado</span>';
                                } elseif ($estado_nuevo == 'Rechazado') {
                                    echo '<span class="badge" style="background-color: #dc3545; color: #fff;">Rechazado</span>';
                                } else {
                                    echo '<span class="badge" style="background-color: #ffc107; color: #333;">Pendiente</span>';
                                }
                            ?>
                        </td>
                        <td class="cell-muted"><?php echo htmlspecialchars($registro['Motivo'] ?? 'Sin motivo especificado'); ?></td>
                        <td class="cell-muted">
                            <small><?php echo date('d/m/Y H:i:s', strtotime($registro['Fecha'])); ?></small>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de registros: <strong><?php echo count($registros_auditoria); ?></strong>
        </div>

        <?php } else { ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No hay registros de auditoría disponibles.</p>
        </div>

        <?php } ?>

    </div>

</div>

<script>
    const inputBuscar = document.getElementById('buscarAuditoria');
    const tabla = document.getElementById('tablaAuditoria');

    if (inputBuscar && tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        const conteo = document.getElementById('conteoAuditoria');

        inputBuscar.addEventListener('input', () => {
            const texto = inputBuscar.value.trim().toLowerCase();
            let visibles = 0;

            filas.forEach(fila => {
                const curso = fila.children[0].textContent.toLowerCase();
                const admin = fila.children[1].textContent.toLowerCase();
                const coincide = curso.includes(texto) || admin.includes(texto);
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            if (conteo) conteo.textContent = visibles;
        });
    }
</script>

<?php include("../footer.php"); ?>
