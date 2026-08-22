<?php include("../autenticacion.php");
include("../bd.php");

if(isset($_GET["txtID"])){
    $txtID = $_GET["txtID"];
    $sentencia = $conexion->prepare("SELECT * FROM Planes WHERE ID = :id");
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    $registro = $sentencia->fetch(PDO::FETCH_LAZY);
    
    if($registro){
        $ID            = $registro["ID"];
        $Nombre        = $registro["Nombre"];
        $Precio        = $registro["Precio"];
        $DuracionDias  = $registro["DuracionDias"];
        $Descuento     = $registro["Descuento"];
    }
}

if($_POST){
    $txtID        = (isset($_POST["ID"])) ? $_POST["ID"] : "";
    $Nombre       = (isset($_POST["Nombre"])) ? $_POST["Nombre"] : "";
    $Precio       = (isset($_POST["Precio"])) ? $_POST["Precio"] : "";
    $DuracionDias = (isset($_POST["DuracionDias"]) && $_POST["DuracionDias"] != "") ? $_POST["DuracionDias"] : null;
    $Descuento    = (isset($_POST["Descuento"])) ? $_POST["Descuento"] : 0.00;

    $sentencia = $conexion->prepare(
        "UPDATE Planes SET
        Nombre = :Nombre,
        Precio = :Precio,
        DuracionDias = :DuracionDias,
        Descuento = :Descuento
        WHERE ID = :id");
    $sentencia->bindParam(":Nombre", $Nombre);
    $sentencia->bindParam(":Precio", $Precio);
    $sentencia->bindParam(":DuracionDias", $DuracionDias);
    $sentencia->bindParam(":Descuento", $Descuento);
    $sentencia->bindParam(":id", $txtID);
    $sentencia->execute();
    
    $mensaje = "Plan actualizado correctamente";
    header("Location: index.php?mensaje=".$mensaje);
    exit;
}
?>
<?php include("../header.php") ?>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">
                <i class="bi bi-pencil-square"></i> Editar Plan
            </h2>
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Datos del plan
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="post">
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-hash"></i> ID:
                            </label>
                            <input type="hidden" value="<?php echo $ID; ?>" name="ID" />
                            <input type="text" value="<?php echo $ID; ?>" class="form-control form-control-lg bg-light" disabled />
                        </div>
                        <div class="mb-4">
                            <label for="Nombre" class="form-label fw-bold">
                                <i class="bi bi-tag"></i> Nombre del plan:
                            </label>
                            <input type="text" value="<?php echo $Nombre; ?>" class="form-control form-control-lg border-2" 
                                   name="Nombre" id="Nombre" required />
                        </div>
                        <div class="mb-4">
                            <label for="Precio" class="form-label fw-bold">
                                <i class="bi bi-currency-dollar"></i> Precio:
                            </label>
                            <input type="number" step="0.01" value="<?php echo $Precio; ?>" class="form-control form-control-lg border-2" 
                                   name="Precio" id="Precio" required />
                        </div>
                        <div class="mb-4">
                            <label for="DuracionDias" class="form-label fw-bold">
                                <i class="bi bi-calendar"></i> Duración (días):
                            </label>
                            <input type="number" value="<?php echo $DuracionDias ?? ''; ?>" class="form-control form-control-lg border-2" 
                                   name="DuracionDias" id="DuracionDias" placeholder="Dejar vacío si no aplica" />
                            <small class="form-text text-muted"></small>
                        </div>
                        <div class="mb-4">
                            <label for="Descuento" class="form-label fw-bold">
                                <i class="bi bi-percent"></i> Descuento:
                            </label>
                            <input type="number" step="0.01" value="<?php echo $Descuento; ?>" class="form-control form-control-lg border-2" 
                                   name="Descuento" id="Descuento" />
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="index.php" class="btn btn-secondary btn-lg">
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