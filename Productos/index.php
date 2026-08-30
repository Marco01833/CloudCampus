<?php
include("../autenticacion.php"); 
include("../bd.php");
include("../header.php");

$id_usuario = $_SESSION['user_id'];
$stmt_plan = $conexion->prepare("SELECT IDPlan FROM Usuarios WHERE ID = ?");
$stmt_plan->execute([$id_usuario]);
$plan_usuario = $stmt_plan->fetch(PDO::FETCH_ASSOC);
$id_plan = $plan_usuario['IDPlan'] ?? 0;
?>

<div class="container py-4">
    <h1 class="mb-4">Catálogo de Cursos</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php
        $stmt = $conexion->prepare("
            SELECT c.*, 
                   u.ID AS profesor_id, 
                   dp.Nombre AS profesor_nombre, 
                   dp.Apellidos AS profesor_apellidos
            FROM cursos c
            JOIN Usuarios u ON c.IDUsuario = u.ID
            LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
            LEFT JOIN Inscripciones i ON i.IDCurso = c.ID AND i.IDUsuario = :id_usuario AND i.Estado = 1
            WHERE c.Estado = 'Aprobado' AND i.ID IS NULL
            ORDER BY c.ID DESC
        ");
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($cursos) === 0) {
            echo '<p class="text-muted">No hay cursos disponibles en este momento.</p>';
        } else {
            foreach ($cursos as $curso) {
                $profesor_nombre_completo = trim(
                    ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellidos'] ?? '')
                );
                if (empty($profesor_nombre_completo)) {
                    $profesor_nombre_completo = 'Profesor';
                }
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($curso['Imagen'])): ?>
                            <img src="../Cursos_Usuario/Imagen/<?= htmlspecialchars($curso['Imagen']) ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 alt="<?= htmlspecialchars($curso['Nombre']) ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($curso['Nombre']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(substr($curso['Descripcion'] ?? 'Sin descripción', 0, 100)) ?>...
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?= htmlspecialchars($profesor_nombre_completo) ?>
                                </small>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="h5 mb-0 text-primary fw-bold">$<?= number_format($curso['Precio'] ?? 0, 2) ?></span>
                                <div>
                                    <a href="../Carrito/agregar.php?id=<?= $curso['ID'] ?>" class="btn btn-danger">
                                        <i class="bi bi-cart-plus"></i> Añadir al carrito
                                    </a>
                                    
                                    <?php if ($id_plan == 2): ?>
                                        <a href="../Cursos_Usuario/contenido.php?id=<?= $curso['ID'] ?>" class="btn btn-primary">
                                            <i class="bi bi-eye"></i> Ver contenido
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

<?php include("../footer.php"); ?>