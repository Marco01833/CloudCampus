<<<<<<< HEAD
<?php
include("../autenticacion.php");
include("../bd.php");
$id_usuario = $_SESSION['user_id'];
$consulta = "SELECT 
                c.ID, 
                c.Nombre AS nombre_curso, 
                c.Descripcion, 
                c.Imagen,
                i.FechaInscripcion
            FROM Inscripciones i
            INNER JOIN Cursos c ON i.IDCurso = c.ID
            WHERE i.IDUsuario = :id_usuario
            AND i.Estado = 1
            ORDER BY i.FechaInscripcion DESC";

$sentencia = $conexion->prepare($consulta);
$sentencia->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$sentencia->execute();
$cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
include("../header.php");
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-book"></i> Mis Cursos Inscritos</h4>
        </div>
        <div class="card-body">
            <?php if (count($cursos) > 0): ?>
                <div class="row">
                    <?php foreach ($cursos as $curso): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($curso['Imagen'])): ?>
                                    <img src="../Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']); ?>"class="card-img-top" alt="<?= htmlspecialchars($curso['nombre_curso']); ?>"style="height: 180px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($curso['nombre_curso']); ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($curso['Descripcion'] ?? ''); ?></p>
                                    
                                    <a href="contenido.php?id=<?= $curso['ID']; ?>" 
                                       class="btn btn-primary mt-2">
                                        <i class="bi bi-play-circle"></i> Ver Contenido
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No tienes cursos aprobados. Por favor, revisa el estado de tus inscripciones.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

=======
<?php
include("../autenticacion.php");
include("../bd.php");
$id_usuario = $_SESSION['user_id'];
$consulta = "SELECT 
                c.ID, 
                c.Nombre AS nombre_curso, 
                c.Descripcion, 
                c.Imagen,
                i.FechaInscripcion
            FROM Inscripciones i
            INNER JOIN Cursos c ON i.IDCurso = c.ID
            WHERE i.IDUsuario = :id_usuario
            AND i.Estado = 1
            ORDER BY i.FechaInscripcion DESC";

$sentencia = $conexion->prepare($consulta);
$sentencia->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$sentencia->execute();
$cursos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
include("../header.php");
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-book"></i> Mis Cursos Inscritos</h4>
        </div>
        <div class="card-body">
            <?php if (count($cursos) > 0): ?>
                <div class="row">
                    <?php foreach ($cursos as $curso): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if (!empty($curso['Imagen'])): ?>
                                    <img src="../Cursos/Imagen/<?= htmlspecialchars($curso['Imagen']); ?>"class="card-img-top" alt="<?= htmlspecialchars($curso['nombre_curso']); ?>"style="height: 180px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($curso['nombre_curso']); ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($curso['Descripcion'] ?? ''); ?></p>
                                    
                                    <a href="contenido.php?id=<?= $curso['ID']; ?>" 
                                       class="btn btn-primary mt-2">
                                        <i class="bi bi-play-circle"></i> Ver Contenido
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No tienes cursos aprobados. Por favor, revisa el estado de tus inscripciones.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

>>>>>>> 74b2e15fd16a840c6153302da58b357e003119e1
<?php include("../footer.php"); ?>