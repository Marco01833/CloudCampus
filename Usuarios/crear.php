<?php include("../bd.php"); 
if($_POST){

    $correo = (isset($_POST["Correo"]) ? $_POST["Correo"] : "");
    $clave  = (isset($_POST["Contraseña"]) ? $_POST["Contraseña"] : "");
    $idRol  = (isset($_POST["IdRol"]) ? $_POST["IdRol"] : "");
    $idPlan = (isset($_POST["IdPlan"]) ? $_POST["IdPlan"] : "");
    $estado = (isset($_POST["Estado"]) ? $_POST["Estado"] : 1);
    $verificado = (isset($_POST["Verificado"]) ? $_POST["Verificado"] : 0);

    if($correo == "" || $clave == "" || $idRol == "" || $idPlan == "") {
        $error = "Todos los campos son obligatorios";
    } else {
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        
        $sentencia = $conexion->prepare("INSERT INTO Usuarios (Correo, Contraseña, Estado, IDRol, IDPlan, Verificado, NumeroSesiones)
        VALUES (:Correo, :Clave, :Estado, :IDRol, :IDPlan, :Verificado, 1)");
        $sentencia->bindParam(":Correo", $correo);
        $sentencia->bindParam(":Clave", $claveHash);
        $sentencia->bindParam(":Estado", $estado);
        $sentencia->bindParam(":IDRol", $idRol);
        $sentencia->bindParam(":IDPlan", $idPlan);
        $sentencia->bindParam(":Verificado", $verificado);
        $sentencia->execute();
        header("Location: usuarios.php");
        exit;
    }
}
?>
<?php include("../header.php") ?>
<br> <br>
<div class="card">
    <div class="card-header">Datos del usuario</div>
    <div class="card-body">
        <?php if(isset($error)) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="Correo" class="form-label">Correo electrónico:</label>
                <input type="email" class="form-control form-control-lg" name="Correo" id="Correo" 
                       placeholder="Ingrese el correo electrónico" required>
            </div>

            <div class="mb-3">
                <label for="Contraseña" class="form-label">Contraseña:</label>
                <input type="password" class="form-control form-control-lg" name="Contraseña" id="Contraseña" 
                       placeholder="Ingrese la contraseña" required>
            </div>

            <div class="mb-3">
                <label for="IdRol" class="form-label">Rol:</label>
                <select name="IdRol" id="IdRol" class="form-select form-select-lg" required>
                    <option value="1">Estudiante</option>
                    <option value="2">Administrador</option>
                    <option value="3">Profesor</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="IdPlan" class="form-label">Plan:</label>
                <select name="IdPlan" id="IdPlan" class="form-select form-select-lg" required>
                    <option value="1">Básico</option>
                    <option value="2">Premium</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="Estado" class="form-label">Estado:</label>
                <select name="Estado" id="Estado" class="form-select form-select-lg" required>
                    <option value="1" selected>HABILITADO</option>
                    <option value="0">INHABILITADO</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="Verificado" class="form-label">Verificado:</label>
                <select name="Verificado" id="Verificado" class="form-select form-select-lg" required>
                    <option value="0" selected>No verificado</option>
                    <option value="1">Verificado</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success btn-lg">Guardar</button>
            <a name="" id="" class="btn btn-primary btn-lg" href="usuarios.php" role="button">Cancelar</a>
        </form>
    </div>
</div>