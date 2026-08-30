<?php include("../autenticacion.php");
include("../bd.php");

if($_POST){
    $nombre      = (isset($_POST["Nombre"]) ? $_POST["Nombre"] : "");
    $precio      = (isset($_POST["Precio"]) ? $_POST["Precio"] : "");
    $duracionDias= (isset($_POST["DuracionDias"]) ? $_POST["DuracionDias"] : null);
    $descuento   = (isset($_POST["Descuento"]) ? $_POST["Descuento"] : 0.00);

    if($nombre == "" || $precio == "") {
        $error = "El nombre y el precio son obligatorios";
    } else {
        $sentencia = $conexion->prepare("INSERT INTO Planes (Nombre, Precio, DuracionDias, Descuento)
                                         VALUES (:Nombre, :Precio, :DuracionDias, :Descuento)");
        $sentencia->bindParam(":Nombre", $nombre);
        $sentencia->bindParam(":Precio", $precio);
        $sentencia->bindParam(":DuracionDias", $duracionDias);
        $sentencia->bindParam(":Descuento", $descuento);
        $sentencia->execute();
        header("Location: index.php");
        exit;
    }
}
?>
<?php include("../header.php") ?>
<br><br>
<div class="card">
    <div class="card-header">Nuevo Plan</div>
    <div class="card-body">
        <?php if(isset($error)) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>
        
        <form action="" method="post">
            <div class="mb-3">
                <label for="Nombre" class="form-label">Nombre del plan:</label>
                <input type="text" class="form-control form-control-lg" name="Nombre" id="Nombre" 
                       placeholder="Ej: Básico, Premium, etc." required>
            </div>

            <div class="mb-3">
                <label for="Precio" class="form-label">Precio:</label>
                <input type="number" step="0.01" class="form-control form-control-lg" name="Precio" id="Precio" 
                       placeholder="0.00" required>
            </div>

            <div class="mb-3">
                <label for="DuracionDias" class="form-label">Duración (días):</label>
                <input type="number" class="form-control form-control-lg" name="DuracionDias" id="DuracionDias" 
                       placeholder="Opcional, dejar vacío si es indefinido">
                <small class="form-text text-muted"></small>
            </div>

            <div class="mb-3">
                <label for="Descuento" class="form-label">Descuento (%):</label>
                <input type="number" step="0.01" class="form-control form-control-lg" name="Descuento" id="Descuento" 
                       placeholder="0.00" value="0.00">
            </div>
            <button type="submit" class="btn btn-success btn-lg">Guardar</button>
            <a href="index.php" class="btn btn-primary btn-lg" role="button">Cancelar</a>
        </form>
    </div>
</div>