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

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <a name="" id="" class="btn btn-outline-primary" href="crear.php" role="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill" viewBox="0 0 16 16">
                    <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                    <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/>
                </svg> Nuevo
            </a>
        </div>
        <form method="get" action="" class="d-flex align-items-center gap-2">
            <label for="rol" class="mb-0 me-1">Filtrar por rol:</label>
            <select name="rol" id="rol" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
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
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table table-bordered tabla-usuarios">
                <thead class="table-primary">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Plan</th>
                        <th scope="col">Sesiones máx.</th>
                        <th scope="col">Verificado</th>
                        <th scope="col">Intentos</th>
                        <th scope="col">Bloqueo (min)</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_usuarios as $registro):
                        $bloqueo_texto = '0';
                        if ($registro['bloqueado_hasta']) {
                            $ahora = new DateTime();
                            $bloqueo = new DateTime($registro['bloqueado_hasta']);
                            if ($ahora < $bloqueo) {
                                $diferencia = $ahora->diff($bloqueo);
                                $minutos = ($diferencia->h * 60) + $diferencia->i;
                                $bloqueo_texto = $minutos;
                            }
                        }
                    ?>
                    <tr>
                        <td><?php echo $registro['ID']; ?></td>
                        <td><?php echo $registro['Correo']; ?></td>
                        <td><?php echo $registro['IDRol'] == 1 ? 'Estudiante' : ($registro['IDRol'] == 2 ? 'Administrador' : 'Profesor'); ?></td>
                        <td><?php echo $registro['IDPlan'] == 1 ? 'Plan Básico' : 'Plan Premium'; ?></td>
                        <td><?php echo $registro['NumeroSesiones'] ?? 2; ?></td>
                        <td><?php echo ($registro['Verificado'] == 1) ? 'SÍ' : 'NO'; ?></td>
                        <td><?php echo $registro['intentos_fallidos']; ?></td>
                        <td><?php echo $bloqueo_texto; ?></td>
                        <td><?php echo ($registro['Estado'] == 1) ? 'HABILITADO' : 'INHABILITADO'; ?></td>
                        <td>
                            <a class="btn btn-outline-success" href="editar.php?txtID=<?php echo $registro['ID']; ?>" role="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 .999z"/>
                                </svg>
                            </a>

                            <?php if ($registro['IDRol'] != 2): ?>
                                <a class="btn btn-outline-danger" href="usuarios.php?accion=eliminar&id=<?php echo $registro['ID']; ?>" 
                                   onclick="return confirm('⚠️ ¿Estás seguro de eliminar permanentemente este usuario?\n\nSe eliminarán TODOS sus datos:\n• Datos personales\n• Sesiones activas\n• Suscripciones\n• Facturas y detalles\n• Inscripciones a cursos\n• Progreso y notas\n• Certificados\n• Cursos creados (con su contenido)\n• Cuestionarios creados\n• Intentos de cuestionarios\n• Correos de verificación y restablecimiento\n\nEsta acción es irreversible.')" role="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <a class="btn btn-outline-primary" href="usuarios.php?accion=cambiar_estado&id=<?php echo $registro['ID']; ?>" 
                               onclick="return confirm('¿Estás seguro de cambiar el estado del usuario?')" role="button">
                               <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9"/>
                                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted">Total de usuarios: <?php echo count($lista_usuarios); ?></div>
</div>

<?php include("../footer.php"); ?>