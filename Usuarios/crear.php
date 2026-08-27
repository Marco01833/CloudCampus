<?php include("../bd.php"); 
if($_POST){

    $correo = (isset($_POST["Correo"]) ? $_POST["Correo"] : "");
    $clave  = (isset($_POST["Contrasena"]) ? $_POST["Contrasena"] : "");
    $idRol  = (isset($_POST["IdRol"]) ? $_POST["IdRol"] : "");
    $idPlan = (isset($_POST["IdPlan"]) ? $_POST["IdPlan"] : "");
    $estado = (isset($_POST["Estado"]) ? $_POST["Estado"] : 1);
    $verificado = (isset($_POST["Verificado"]) ? $_POST["Verificado"] : 0);

    if($correo == "" || $clave == ""  ) {
        $error = "Todos los campos son obligatorios";
    } else {
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        
        $sentencia = $conexion->prepare("INSERT INTO Usuarios (Correo, Contrasena, Estado, IDRol, IDPlan, Verificado, NumeroSesiones)
        VALUES (:Correo, :Clave, 1, 3, 1, 1, 2)");
        $sentencia->bindParam(":Correo", $correo);
        $sentencia->bindParam(":Clave", $claveHash);
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
                <label for="Contrasena" class="form-label">Contraseña:</label>
                <input type="password" class="form-control form-control-lg" name="Contrasena" id="Contraseña" 
                       placeholder="Ingrese la contraseña" required>
            </div>

            <button type="submit" class="btn btn-success btn-lg">Guardar</button>
            <a name="" id="" class="btn btn-primary btn-lg" href="usuarios.php" role="button">Cancelar</a>
        </form>
    </div>
</div>