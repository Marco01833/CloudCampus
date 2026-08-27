<?php include("../bd.php"); 

if(isset($_GET["txtID"])){
    $txtID = (isset($_GET["txtID"])) ? $_GET["txtID"] : "";
    $sentencia = $conexion->prepare("SELECT * FROM Usuarios WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    
    if($registro){
        $Correo      = $registro["Correo"];
        $Contraseña  = $registro["Contraseña"];
        $Estado      = $registro["Estado"];
        $IDRol       = $registro["IDRol"];
        $IDPlan      = $registro["IDPlan"];
        $Verificado  = $registro["Verificado"]; 
    }
}

if($_POST){
    $txtID      = (isset($_POST["ID"])) ? $_POST["ID"] : "";
    $Correo     = (isset($_POST["Correo"])) ? $_POST["Correo"] : "";
    $Contraseña = (isset($_POST["Contraseña"])) ? $_POST["Contraseña"] : "";
    $Estado     = (isset($_POST["Estado"])) ? $_POST["Estado"] : "";
    $IDRol      = (isset($_POST["IdRol"])) ? $_POST["IdRol"] : "";
    $IDPlan     = (isset($_POST["IdPlan"])) ? $_POST["IdPlan"] : "";
    $Verificado = (isset($_POST["Verificado"])) ? $_POST["Verificado"] : 0; 

    if(!empty($Contraseña)) {
        $ContraseñaHash = password_hash($Contraseña, PASSWORD_DEFAULT);
        $sentencia = $conexion->prepare("UPDATE Usuarios SET
            Correo = :Correo,
            Contraseña = :Contraseña,
            Estado = :Estado,
            IDRol = :IDRol,
            IDPlan = :IDPlan,
            Verificado = :Verificado
            WHERE ID = :id");
        $sentencia->bindParam(":Contraseña", $ContraseñaHash);
    } else {
        $sentencia = $conexion->prepare("UPDATE Usuarios SET
            Correo = :Correo,
            Estado = :Estado,
            IDRol = :IDRol,
            IDPlan = :IDPlan,
            Verificado = :Verificado
            WHERE ID = :id");
    }
    $sentencia->bindParam(":Correo", $Correo);
    $sentencia->bindParam(":Estado", $Estado);
    $sentencia->bindParam(":IDRol", $IDRol);
    $sentencia->bindParam(":IDPlan", $IDPlan);
    $sentencia->bindParam(":Verificado", $Verificado); 
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    
    $mensaje = "Registro actualizado";
    header("Location: usuarios.php?mensaje=".$mensaje);
    exit;
}

?>
<?php include("../header.php") ?>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">
                <i class="bi bi-pencil-square"></i> Editar Usuario
            </h2>
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Datos del usuario
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="ID" class="form-label fw-bold">
                                <i class="bi bi-hash"></i> ID:
                            </label>
                            <input type="hidden" value="<?php echo $txtID; ?>" name="ID" />
                            <input type="text" value="<?php echo $txtID; ?>" class="form-control form-control-lg bg-light" disabled />
                        </div>

                        <div class="mb-4">
                            <label for="Correo" class="form-label fw-bold">
                                <i class="bi bi-envelope"></i> Correo electrónico:
                            </label>
                            <input type="email" value="<?php echo $Correo ?? ''; ?>" class="form-control form-control-lg border-2" 
                                   name="Correo" id="Correo" placeholder="Correo electrónico" required/>
                            <small class="form-text text-muted d-block mt-2"></small>
                        </div>

                        <div class="mb-4">
                            <label for="Contraseña" class="form-label fw-bold">
                                <i class="bi bi-lock"></i> Contraseña:
                            </label>
                            <input type="password" class="form-control form-control-lg border-2 bg-light" 
                                   name="Contraseña" id="Contraseña" value="********" disabled />
                            <small class="form-text text-muted d-block mt-2"></small>
                        </div>

                        <div class="mb-3">
                            <label for="IdRol" class="form-label fw-bold">Rol:</label>
                            <select name="IdRol" id="IdRol" class="form-select form-select-lg" required>
                                <option value="1" <?php echo ($IDRol == 1) ? 'selected' : ''; ?>>Estudiante</option>
                                <option value="2" <?php echo ($IDRol == 2) ? 'selected' : ''; ?>>Administrador</option>
                                <option value="3" <?php echo ($IDRol == 3) ? 'selected' : ''; ?>>Profesor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="IdPlan" class="form-label fw-bold">Plan:</label>
                            <select name="IdPlan" id="IdPlan" class="form-select form-select-lg" required>
                                <option value="1" <?php echo ($IDPlan == 1) ? 'selected' : ''; ?>>Básico</option>
                                <option value="2" <?php echo ($IDPlan == 2) ? 'selected' : ''; ?>>Premium</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="Verificado" class="form-label fw-bold">Verificado:</label> 
                            <select name="Verificado" id="Verificado" class="form-select form-select-lg" required>
                                <option value="1" <?php echo ($Verificado == 1) ? 'selected' : ''; ?>>Sí</option>
                                <option value="0" <?php echo ($Verificado == 0) ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="Estado" class="form-label fw-bold">Estado:</label>
                            <select name="Estado" id="Estado" class="form-select form-select-lg" required>
                                <option value="1" <?php echo ($Estado == 1) ? 'selected' : ''; ?>>HABILITADO</option>
                                <option value="0" <?php echo ($Estado == 0) ? 'selected' : ''; ?>>INHABILITADO</option>
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