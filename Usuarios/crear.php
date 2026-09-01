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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario</title>
    <link rel="stylesheet" href="../css/crear.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="admin-wrap">
    <div class="admin-card">

        <div class="admin-card-header">
            <i class="fa-regular fa-user"></i> Datos del usuario
        </div>

        <div class="admin-card-body">

            <?php if(isset($error)) { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <form action="" method="post" enctype="multipart/form-data">

                <div class="field-group">
                    <label for="Correo">Correo electrónico</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="Correo" id="Correo"
                               placeholder="Ingrese el correo electrónico" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="Contraseña">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="Contrasena" id="Contraseña"
                               placeholder="Ingrese la contraseña" required>
                        <button type="button" class="toggle-password" tabindex="-1" onclick="
                            const i=this.previousElementSibling;
                            i.type = i.type === 'password' ? 'text' : 'password';
                            this.querySelector('i').classList.toggle('fa-eye');
                            this.querySelector('i').classList.toggle('fa-eye-slash');
                        ">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                    <a class="btn btn-ghost" href="usuarios.php" role="button">Cancelar</a>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>