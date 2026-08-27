<?php
include("../autenticacion.php");
include("../bd.php");
$id_usuario = $_SESSION['user_id'];
$rol_usuario = $_SESSION['rol'] ?? 0;

if ($rol_usuario == 2 || $rol_usuario == 3) {
    $consulta = "SELECT 
                    c.ID AS ID, 
                    c.Nombre AS nombre_curso, 
                    c.Descripcion AS Descripcion, 
                    c.Imagen AS Imagen,
                    c.Estado AS Estado
                FROM cursos c
                WHERE c.IDUsuario = :id_usuario
                ORDER BY c.ID DESC";
} else {
    $consulta = "SELECT 
                    c.ID AS ID, 
                    c.Nombre AS nombre_curso, 
                    c.Descripcion AS Descripcion, 
                    c.Imagen AS Imagen,
                    i.FechaInscripcion AS FechaInscripcion
                FROM Inscripciones i
                JOIN cursos c ON i.IDCurso = c.ID
                WHERE i.IDUsuario = :id_usuario
                ORDER BY i.FechaInscripcion DESC";
}
$sentencia = $conexion->prepare($consulta);
$sentencia->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$sentencia->execute();
$cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

include("../header.php");
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-book"></i> <?= ($rol_usuario == 2 || $rol_usuario == 3) ? 'Mis Cursos' : 'Mis Cursos Inscritos' ?></h4>
            <?php if ($rol_usuario == 2 || $rol_usuario == 3): ?>
                <a href="crear.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle"></i> Nuevo Curso
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (count($cursos) > 0): ?>
                <div class="row">
                    <?php foreach ($cursos as $curso): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 position-relative">
                                <?php 
                                    $mostrarEliminar = false;
                                    if ($rol_usuario == 2) {
                                        $mostrarEliminar = ($curso['Estado'] != 'Aprobado');
                                    } elseif ($rol_usuario == 3) {
                                        $mostrarEliminar = in_array($curso['Estado'], ['Pendiente', 'Rechazado']);
                                    }
                                ?>
                                <?php if ($mostrarEliminar): ?>
                                    <form method="post" action="Eliminar.php" style="position:absolute; top:10px; left:10px; z-index:10;">
                                        <input type="hidden" name="id" value="<?= $curso['ID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('¿Está seguro de eliminar este curso? Se eliminarán todos los contenidos y registros asociados.')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($curso['Imagen'])): ?>
                                    <img src="../Cursos_Usuario/Imagen/<?= htmlspecialchars($curso['Imagen']); ?>" class="card-img-top" alt="<?= htmlspecialchars($curso['nombre_curso']); ?>" style="height: 180px; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($curso['nombre_curso']); ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($curso['Descripcion'] ?? ''); ?></p>
                                    <?php if ($rol_usuario == 2 || $rol_usuario == 3): ?>
                                        <p class="card-text"><strong>Estado:</strong> <?= htmlspecialchars($curso['Estado'] ?? ''); ?></p>
                                    <?php endif; ?>
                                    <a href="contenido.php?id=<?= $curso['ID']; ?>" class="btn btn-primary mt-2">
                                        <i class="bi bi-play-circle"></i> Ver Contenido
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php if ($rol_usuario == 2 || $rol_usuario == 3): ?>
                        No has creado ningún curso.
                    <?php else: ?>
                        No tienes cursos aprobados. Por favor, revisa el estado de tus inscripciones.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("../footer.php"); ?>