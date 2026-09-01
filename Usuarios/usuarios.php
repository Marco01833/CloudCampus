<?php
include("../autenticacion.php");
include("../bd.php");

$filtro_rol = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
$roles_validos = [0, 1, 2, 3];
if (!in_array($filtro_rol, $roles_validos)) $filtro_rol = 0;

if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $check = $conexion->prepare("SELECT ID, IDRol FROM Usuarios WHERE ID = ?");
    $check->execute([$id]);
    $usuario_data = $check->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_data) {
        header("Location: usuarios.php?mensaje=" . urlencode("Usuario no encontrado"));
        exit;
    }

    if ($usuario_data['IDRol'] == 2) {
        $mensaje = "No se puede eliminar a un administrador.";
        header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
        exit;
    }

    if ($usuario_data['IDRol'] == 3) {
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM Inscripciones i JOIN cursos c ON i.IDCurso = c.ID WHERE c.IDUsuario = ?");
        $stmt->execute([$id]);
        $num_inscripciones = $stmt->fetchColumn();
        if ($num_inscripciones > 0) {
            $mensaje = "No se puede eliminar al profesor porque tiene cursos con inscripciones";
            header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
            exit;
        }
    }

    try {
        $conexion->beginTransaction();
        $stmt = $conexion->prepare("DELETE FROM verificacion_email WHERE user_id = ?");
        $stmt->execute([$id]);
        $stmt = $conexion->prepare("DELETE FROM restablecer_contrasena WHERE user_id = ?");
        $stmt->execute([$id]);

        $stmt = $conexion->prepare("DELETE FROM SesionesActivas WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $stmt = $conexion->prepare("DELETE FROM Suscripciones WHERE IDUsuario = ?");
        $stmt->execute([$id]);

        $stmt = $conexion->prepare("SELECT ID FROM Facturas WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $facturas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($facturas) {
            $placeholders = implode(',', array_fill(0, count($facturas), '?'));
            $stmt = $conexion->prepare("DELETE FROM DetalleFactura WHERE IDFactura IN ($placeholders)");
            $stmt->execute($facturas);
            $stmt = $conexion->prepare("DELETE FROM Facturas WHERE IDUsuario = ?");
            $stmt->execute([$id]);
        }

        $stmt = $conexion->prepare("DELETE FROM Inscripciones WHERE IDUsuario = ?");
        $stmt->execute([$id]);

        $stmt = $conexion->prepare("DELETE FROM NotasCurso WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $stmt = $conexion->prepare("DELETE FROM progreso_estudiante WHERE IDUsuario = ?");
        $stmt->execute([$id]);

        $stmt = $conexion->prepare("SELECT ID FROM IntentosCuestionario WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $intentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($intentos) {
            $placeholders = implode(',', array_fill(0, count($intentos), '?'));
            $stmt = $conexion->prepare("DELETE FROM RespuestasUsuario WHERE IDIntento IN ($placeholders)");
            $stmt->execute($intentos);
            $stmt = $conexion->prepare("DELETE FROM IntentosCuestionario WHERE IDUsuario = ?");
            $stmt->execute([$id]);
        }

        $stmt = $conexion->prepare("DELETE FROM certificados WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $stmt = $conexion->prepare("SELECT ID FROM Cuestionarios WHERE IDCreador = ?");
        $stmt->execute([$id]);
        $cuestionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($cuestionarios) {
            $placeholders = implode(',', array_fill(0, count($cuestionarios), '?'));
            $stmt = $conexion->prepare("DELETE FROM Opciones WHERE IDPregunta IN (SELECT ID FROM Preguntas WHERE IDCuestionario IN ($placeholders))");
            $stmt->execute($cuestionarios);
            $stmt = $conexion->prepare("DELETE FROM Preguntas WHERE IDCuestionario IN ($placeholders)");
            $stmt->execute($cuestionarios);
            $stmt = $conexion->prepare("DELETE FROM Cuestionarios WHERE IDCreador = ?");
            $stmt->execute([$id]);
        }

        $stmt = $conexion->prepare("SELECT ID FROM cursos WHERE IDUsuario = ?");
        $stmt->execute([$id]);
        $cursos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($cursos) {
            $placeholders = implode(',', array_fill(0, count($cursos), '?'));
            $stmt = $conexion->prepare("DELETE FROM Contenido WHERE IDCurso IN ($placeholders)");
            $stmt->execute($cursos);
            $stmt = $conexion->prepare("DELETE FROM cursos WHERE IDUsuario = ?");
            $stmt->execute([$id]);
        }

        $stmt = $conexion->prepare("DELETE FROM DatosPersonales WHERE IDUsuario = ?");
        $stmt->execute([$id]);

        $stmt = $conexion->prepare("DELETE FROM Usuarios WHERE ID = ?");
        $stmt->execute([$id]);

        $conexion->commit();
        $mensaje = "Usuario eliminado correctamente junto con todos sus datos asociados.";
        header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
        exit;

    } catch (PDOException $e) {
        $conexion->rollBack();
        $mensaje = "Error al eliminar el usuario: " . $e->getMessage();
        header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
        exit;
    }
}

if (isset($_GET['accion']) && $_GET['accion'] == 'cambiar_estado' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sentencia = $conexion->prepare("SELECT Estado FROM Usuarios WHERE ID = :id");
    $sentencia->bindParam(":id", $id);
    $sentencia->execute();
    $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $nuevo_estado = ($usuario['Estado'] == 1) ? 0 : 1;
        $sentencia = $conexion->prepare("UPDATE Usuarios SET Estado = :estado WHERE ID = :id");
        $sentencia->bindParam(":estado", $nuevo_estado);
        $sentencia->bindParam(":id", $id);
        $sentencia->execute();
        $estado_texto = ($nuevo_estado == 1) ? "HABILITADO" : "INHABILITADO";
        $mensaje = "Usuario cambiado a $estado_texto correctamente.";
    } else {
        $mensaje = "Usuario no encontrado.";
    }
    header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
    exit;
}

$sql = "SELECT ID, Correo, Estado, IDRol, IDPlan, Verificado, intentos_fallidos, bloqueado_hasta, NumeroSesiones FROM Usuarios";
$params = [];
if ($filtro_rol > 0) {
    $sql .= " WHERE IDRol = ?";
    $params[] = $filtro_rol;
}
$sentencia = $conexion->prepare($sql);
$sentencia->execute($params);
$lista_usuarios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>

<?php if (isset($_GET['mensaje'])) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <?php echo htmlspecialchars($_GET['mensaje']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<link rel="stylesheet" href="../css/usuarios.css">

<div class="admin-wrap admin-wrap-wide">

    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// panel</span>
            <h1>Usuarios</h1>
            <p>Gestioná cuentas, roles, planes y accesos de la plataforma.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Nuevo
        </a>
    </div>

    <?php if (isset($_GET['mensaje'])) { ?>
        <div class="alert-box success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($_GET['mensaje']); ?></span>
            <button type="button" class="alert-box-close" onclick="this.closest('.alert-box').remove()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php } ?>

    <div class="admin-card">

        <div class="table-toolbar">
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="buscarCorreo" placeholder="Buscar por correo...">
            </div>
            <div class="table-toolbar-actions d-flex flex-wrap align-items-center gap-3 p-3 bg-light rounded-3 shadow-sm">
    <form method="get" action="" class="filter-form d-flex align-items-center gap-2 flex-wrap">
        <label for="rol" class="fw-semibold text-secondary mb-0">Filtrar por rol:</label>
        <select name="rol" id="rol" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="0" <?= ($filtro_rol == 0) ? 'selected' : '' ?>>Todos</option>
            <option value="1" <?= ($filtro_rol == 1) ? 'selected' : '' ?>>Estudiante</option>
            <option value="2" <?= ($filtro_rol == 2) ? 'selected' : '' ?>>Administrador</option>
            <option value="3" <?= ($filtro_rol == 3) ? 'selected' : '' ?>>Profesor</option>
        </select>
        <noscript>
            <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        </noscript>
        <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </form>
    <span class="table-count ms-auto fw-light text-secondary">
        <span class="badge bg-primary rounded-pill me-1" id="conteoUsuarios"><?php echo count($lista_usuarios); ?></span>
        usuarios encontrados
    </span>
</div>
            
        </div>

        <?php if(count($lista_usuarios) > 0) { ?>

        <div class="table-scroll">
            <table class="data-table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Plan</th>
                        <th>Sesiones máx.</th>
                        <th>Verificado</th>
                        <th>Intentos</th>
                        <th>Bloqueo (min)</th>
                        <th>Estado</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_usuarios as $registro):

                        // ----- Rol -----
                        if($registro['IDRol'] == 1){
                            $rolTexto = 'Estudiante';
                            $rolClase = 'role-student';
                        } elseif($registro['IDRol'] == 2){
                            $rolTexto = 'Administrador';
                            $rolClase = 'role-admin';
                        } else {
                            $rolTexto = 'Profesor';
                            $rolClase = 'role-teacher';
                        }

                        // ----- Plan -----
                        if($registro['IDPlan'] == 1){
                            $planTexto = 'Plan Básico';
                            $planClase = 'plan-basico';
                        } else {
                            $planTexto = 'Plan Premium';
                            $planClase = 'plan-premium';
                        }

                        // ----- Bloqueo (minutos restantes) -----
                        $bloqueo_texto = '0';
                        if ($registro['bloqueado_hasta']) {
                            $ahora = new DateTime();
                            $bloqueo = new DateTime($registro['bloqueado_hasta']);
                            if ($ahora < $bloqueo) {
                                $diferencia = $ahora->diff($bloqueo);
                                $minutos = ($diferencia->h * 60) + $diferencia->i;
                                $bloqueo_texto = $minutos;
                            } else {
                                $bloqueo_texto = '0';
                            }
                        }
                        $bloqueoClase = ($bloqueo_texto > 0) ? 'cell-danger' : 'cell-muted';
                    ?>
                    <tr>
                        <td class="cell-id">#<?php echo $registro['ID']; ?></td>
                        <td><?php echo htmlspecialchars($registro['Correo']); ?></td>
                        <td><span class="badge badge-role <?php echo $rolClase; ?>"><?php echo $rolTexto; ?></span></td>
                        <td><span class="badge badge-plan <?php echo $planClase; ?>"><?php echo $planTexto; ?></span></td>
                        <td><?php echo $registro['NumeroSesiones'] ?? 2; ?></td>
                        <td>
                            <?php if($registro['Verificado'] == 1): ?>
                                <span class="badge badge-yes"><i class="fa-solid fa-check"></i> SÍ</span>
                            <?php else: ?>
                                <span class="badge badge-no"><i class="fa-solid fa-xmark"></i> NO</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $registro['intentos_fallidos']; ?></td>
                        <td class="<?php echo $bloqueoClase; ?>"><?php echo $bloqueo_texto; ?></td>
                        <td>
                            <?php if($registro['Estado'] == 1): ?>
                                <span class="badge badge-status status-on">HABILITADO</span>
                            <?php else: ?>
                                <span class="badge badge-status status-off">INHABILITADO</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <a class="icon-btn" href="editar.php?txtID=<?php echo $registro['ID']; ?>" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <?php if ($registro['IDRol'] != 2): ?>
                                <a class="icon-btn icon-btn-danger" href="usuarios.php?accion=eliminar&id=<?php echo $registro['ID']; ?>"
                                   onclick="return confirm('⚠️ ¿Estás seguro de eliminar permanentemente este usuario?\n\nSe eliminarán TODOS sus datos:\n• Datos personales\n• Sesiones activas\n• Suscripciones\n• Facturas y detalles\n• Inscripciones a cursos\n• Progreso y notas\n• Certificados\n• Cursos creados (con su contenido)\n• Cuestionarios creados\n• Intentos de cuestionarios\n• Correos de verificación y restablecimiento\n\nEsta acción es irreversible.')" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                <?php endif; ?>
                                <a class="icon-btn" href="usuarios.php?accion=cambiar_estado&id=<?php echo $registro['ID']; ?>"
                                   onclick="return confirm('¿Estás seguro de cambiar el estado del usuario?')" title="Cambiar estado">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Total de usuarios: <strong><?php echo count($lista_usuarios); ?></strong>
        </div>

        <?php } else { ?>

        <div class="table-empty">
            <i class="fa-regular fa-folder-open"></i>
            <p>No hay usuarios registrados todavía.</p>
            <a href="crear.php" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Crear el primero
            </a>
        </div>

        <?php } ?>

    </div>

</div>

<script>
    const inputBuscar = document.getElementById('buscarCorreo');
    const tabla = document.getElementById('tablaUsuarios');

    if (inputBuscar && tabla) {
        const filas = tabla.querySelectorAll('tbody tr');
        const conteo = document.getElementById('conteoUsuarios');

        inputBuscar.addEventListener('input', () => {
            const texto = inputBuscar.value.trim().toLowerCase();
            let visibles = 0;

            filas.forEach(fila => {
                const correo = fila.children[1].textContent.toLowerCase();
                const coincide = correo.includes(texto);
                fila.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });

            if (conteo) conteo.textContent = visibles;
        });
    }
</script>

<?php include("../footer.php"); ?>