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
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Editar Usuario</h2>
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del usuario</h5>
                </div>
                
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <input type="hidden" name="ID" value="<?= $txtID ?>">
                        

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-envelope"></i> Correo electrónico:</label>
                            <p class="form-control-plaintext fs-5"><?= htmlspecialchars($Correo ?? '') ?></p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-lock"></i> Contraseña:</label>
                            <p class="form-control-plaintext fs-5">********</p>
                            <small class="text-muted"></small>
                        </div>


                        <div class="mb-3">
                            <label for="IdRol" class="form-label fw-bold">Rol:</label>
                            <select name="IdRol" id="IdRol" class="form-select form-select-lg" required>
                                <option value="1" <?= ($IDRol == 1) ? 'selected' : '' ?>>Estudiante</option>
                                <option value="2" <?= ($IDRol == 2) ? 'selected' : '' ?>>Administrador</option>
                                <option value="3" <?= ($IDRol == 3) ? 'selected' : '' ?>>Profesor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="Verificado" class="form-label fw-bold">Verificado:</label>
                            <select name="Verificado" id="Verificado" class="form-select form-select-lg" required>
                                <option value="1" <?= ($Verificado == 1) ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($Verificado == 0) ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="Estado" class="form-label fw-bold">Estado:</label>
                            <select name="Estado" id="Estado" class="form-select form-select-lg" required>
                                <option value="1" <?= ($Estado == 1) ? 'selected' : '' ?>>HABILITADO</option>
                                <option value="0" <?= ($Estado == 0) ? 'selected' : '' ?>>INHABILITADO</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="usuarios.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>