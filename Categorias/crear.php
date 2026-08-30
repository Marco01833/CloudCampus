<?php
include("../autenticacion.php");
include("../bd.php");

if ($_POST) {
    $Nombre = trim($_POST['Nombre'] ?? '');
    $Descripcion = trim($_POST['Descripcion'] ?? '');

    if (empty($Nombre)) {
        $error = "El nombre de la categoría es obligatorio.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO categoria (Nombre, Descripcion) VALUES (?, ?)");
        $stmt->execute([$Nombre, $Descripcion]);
        $mensaje = "Categoría creada correctamente.";
        header("Location: index.php?mensaje=" . urlencode($mensaje));
        exit;
    }
}

include("../header.php");
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Nueva Categoría</h2>
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos de la categoría</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="mb-4">
                            <label for="Nombre" class="form-label fw-bold">
                                <i class="bi bi-tag"></i> Nombre:
                            </label>
                            <input type="text" class="form-control form-control-lg border-2" 
                                   name="Nombre" id="Nombre" required
                                   placeholder="Ej: Programación"/>
                            <small class="form-text text-muted d-block mt-2">Ingrese el nombre de la categoría</small>
                        </div>

                        <div class="mb-4">
                            <label for="Descripcion" class="form-label fw-bold">
                                <i class="bi bi-card-text"></i> Descripción:
                            </label>
                            <textarea class="form-control form-control-lg border-2" 
                                      name="Descripcion" id="Descripcion" rows="3"
                                      placeholder="Descripción de la categoría"></textarea>
                            <small class="form-text text-muted d-block mt-2">Descripción opcional</small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>