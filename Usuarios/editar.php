<?php
include("../bd.php");
include("../autenticacion.php");

$txtID = isset($_GET['txtID']) ? (int)$_GET['txtID'] : 0;

if ($txtID > 0) {
    $sentencia = $conexion->prepare("SELECT * FROM Usuarios WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_ASSOC);
    
    if ($registro) {
        $Correo      = $registro["Correo"];
        $Estado      = $registro["Estado"];
        $IDRol       = $registro["IDRol"];
        $IDPlan      = $registro["IDPlan"];
        $Verificado  = $registro["Verificado"];
    } else {
        header("Location: usuarios.php?mensaje=Usuario no encontrado");
        exit;
    }
} else {
    header("Location: usuarios.php?mensaje=ID inválido");
    exit;
}

if ($_POST) {
    $txtID      = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;
    $IDRol      = isset($_POST['IdRol']) ? (int)$_POST['IdRol'] : 1;
    $Verificado = isset($_POST['Verificado']) ? (int)$_POST['Verificado'] : 0;
    $Estado     = isset($_POST['Estado']) ? (int)$_POST['Estado'] : 1;
    
    if ($txtID > 0) {
        $sentencia = $conexion->prepare("UPDATE Usuarios SET
            IDRol = :IDRol,
            Verificado = :Verificado,
            Estado = :Estado
            WHERE ID = :id");
        
        $sentencia->bindParam(":IDRol", $IDRol);
        $sentencia->bindParam(":Verificado", $Verificado);
        $sentencia->bindParam(":Estado", $Estado);
        $sentencia->bindParam(":id", $txtID);
        $sentencia->execute();
        
        $mensaje = "Usuario actualizado correctamente";
        header("Location: usuarios.php?mensaje=" . urlencode($mensaje));
        exit;
    } else {
        $error = "ID inválido para actualizar";
    }
}

include("../header.php");
?>
<link rel="stylesheet" href="../css/editar.css">

<div class="admin-wrap">
    <div class="admin-page-header">
        <div>
            <span class="eyebrow">// editar contenido</span>
            <h1>Editar usuario</h1>
            <p>Actualizá el correo, rol, verificación y estado del usuario.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <i class="fa-solid fa-pen-to-square"></i> Datos del usuario
        </div>

        <div class="admin-card-body">

            <form action="" method="post">

                <input type="hidden" name="ID" value="<?= $txtID ?>">

                <div class="field-group">
                    <label for="Correo">Correo electrónico</label>
                    <div class="input-wrapper disabled">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" value="<?= htmlspecialchars($Correo ?? '') ?>" 
                               id="Correo" placeholder="Correo electrónico" disabled>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Contraseña">Contraseña</label>
                    <div class="input-wrapper disabled">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="Contraseña" value="********" disabled>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label for="IdRol">Rol</label>
                        <div class="select-wrapper">
                            <i class="fa-regular fa-id-badge select-icon"></i>
                            <select name="IdRol" id="IdRol" required>
                                <option value="1" <?= ($IDRol == 1) ? 'selected' : '' ?>>Estudiante</option>
                                <option value="2" <?= ($IDRol == 2) ? 'selected' : '' ?>>Administrador</option>
                                <option value="3" <?= ($IDRol == 3) ? 'selected' : '' ?>>Profesor</option>
                            </select>
                            <i class="fa-solid fa-chevron-down select-caret"></i>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="Verificado">Verificado</label>
                        <div class="select-wrapper">
                            <i class="fa-regular fa-shield select-icon"></i>
                            <select name="Verificado" id="Verificado" required>
                                <option value="1" <?= ($Verificado == 1) ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($Verificado == 0) ? 'selected' : '' ?>>No</option>
                            </select>
                            <i class="fa-solid fa-chevron-down select-caret"></i>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Estado">Estado</label>
                    <div class="select-wrapper">
                        <i class="fa-regular fa-circle-check select-icon"></i>
                        <select name="Estado" id="Estado" required>
                            <option value="1" <?= ($Estado == 1) ? 'selected' : '' ?>>HABILITADO</option>
                            <option value="0" <?= ($Estado == 0) ? 'selected' : '' ?>>INHABILITADO</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-caret"></i>
                    </div>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check"></i> Actualizar
                    </button>
                    <a class="btn btn-ghost" href="usuarios.php" role="button">
                        <i class="fa-solid fa-xmark"></i> Cancelar
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include("../footer.php"); ?>