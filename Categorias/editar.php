<?php
include("../autenticacion.php");
include("../bd.php");

$txtID = isset($_GET['txtID']) ? (int)$_GET['txtID'] : 0;
$categoria = null;

if ($txtID > 0) {
    $stmt = $conexion->prepare("SELECT * FROM categoria WHERE IDCategoria = ?");
    $stmt->execute([$txtID]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoria) {
        header("Location: index.php?mensaje=" . urlencode("Categoría no encontrada"));
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

if ($_POST) {
    $Nombre = trim($_POST['Nombre'] ?? '');
    $Descripcion = trim($_POST['Descripcion'] ?? '');

    if (empty($Nombre)) {
        $error = "El nombre de la categoría es obligatorio.";
    } else {
        $stmt = $conexion->prepare("UPDATE categoria SET Nombre = ?, Descripcion = ? WHERE IDCategoria = ?");
        $stmt->execute([$Nombre, $Descripcion, $txtID]);
        $mensaje = "Categoría actualizada correctamente.";
        header("Location: index.php?mensaje=" . urlencode($mensaje));
        exit;
    }
}

include("../header.php");
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Editar Categoría</h2>
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
                            <label class="form-label fw-bold">
                                <i class="bi bi-hash"></i> ID:
                            </label>
                            <input type="text" value="<?= $txtID ?>" class="form-control form-control-lg bg-light" disabled />
                        </div>

                        <div class="mb-4">
                            <label for="Nombre" class="form-label fw-bold">
                                <i class="bi bi-tag"></i> Nombre:
                            </label>
                            <input type="text" class="form-control form-control-lg border-2" 
                                   name="Nombre" id="Nombre" required
                                   value="<?= htmlspecialchars($categoria['Nombre']) ?>"/>
                            <small class="form-text text-muted d-block mt-2">Ingrese el nombre de la categoría</small>
                        </div>

                        <div class="mb-4">
                            <label for="Descripcion" class="form-label fw-bold">
                                <i class="bi bi-card-text"></i> Descripción:
                            </label>
                            <textarea class="form-control form-control-lg border-2" 
                                      name="Descripcion" id="Descripcion" rows="3"
                                      placeholder="Descripción de la categoría"><?= htmlspecialchars($categoria['Descripcion'] ?? '') ?></textarea>
                            <small class="form-text text-muted d-block mt-2">Descripción opcional</small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
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

<?php include("../footer.php"); ?>