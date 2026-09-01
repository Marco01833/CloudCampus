<?php
include("../autenticacion.php"); 
include("../bd.php");
include("../header.php");
?>
<?php
$id_usuario = $_SESSION['user_id'];
$stmt_plan = $conexion->prepare("SELECT IDPlan FROM Usuarios WHERE ID = ?");
$stmt_plan->execute([$id_usuario]);
$plan_usuario = $stmt_plan->fetch(PDO::FETCH_ASSOC);
$id_plan = $plan_usuario['IDPlan'] ?? 0;

$stmt = $conexion->prepare("
    SELECT c.*, 
           u.ID AS profesor_id, 
           dp.Nombre AS profesor_nombre, 
           dp.Apellidos AS profesor_apellidos,
           cat.Nombre AS categoria_nombre
    FROM cursos c
    JOIN Usuarios u ON c.IDUsuario = u.ID
    LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario
    LEFT JOIN categoria cat ON c.IDCategoria = cat.IDCategoria
    LEFT JOIN Inscripciones i ON i.IDCurso = c.ID AND i.IDUsuario = :id_usuario AND i.Estado = 1
    WHERE c.Estado = 'Aprobado' AND i.ID IS NULL
    ORDER BY c.ID DESC
");
$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_cursos = count($cursos);
$stmt_categorias = $conexion->prepare("SELECT IDCategoria, Nombre FROM categoria ORDER BY Nombre ASC");
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);



?>
<link rel="stylesheet" href="../css/cursos.css">

<style>
    .filters-wrapper {
        margin: 1.5rem 0;
        padding: 0.5rem 0;
        background: transparent;
        border-bottom: 1px solid var(--border, #e9ecef);
    }

    .filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        border-right: 1px solid var(--border, #dee2e6);
        padding-right: 1rem;
        margin-right: 1rem;
    }

    .filter-group:last-of-type {
        border-right: none;
        padding-right: 0;
        margin-right: 0;
    }

    .filter-label {
        font-weight: 600;
        color: var(--muted, #6c757d);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .chip {
        background: var(--surface, #f1f3f5);
        border: 2px solid var(--border, #dee2e6);
        border-radius: 30px;
        padding: 0.5rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--ink, #212529);
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        outline: none;
        user-select: none;
        white-space: nowrap;
    }

    .chip:hover {
        background: #e9ecef;
        border-color: #adb5bd;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .chip.active {
        background: var(--red, #e31e24);
        border-color: var(--red, #e31e24);
        color: #fff;
        box-shadow: 0 4px 12px rgba(227, 30, 36, 0.25);
        transform: translateY(-2px);
    }

    .chip.active::after {
        content: " ✓";
        font-weight: 700;
    }

    .chip.active[data-filter="principiante"] {
        background: #28a745;
        border-color: #28a745;
    }
    .chip.active[data-filter="intermedio"] {
        background: #fd7e14;
        border-color: #fd7e14;
    }
    .chip.active[data-filter="avanzado"] {
        background: #dc3545;
        border-color: #dc3545;
    }

    .chip[data-filter="principiante"]:hover {
        border-color: #28a745;
    }
    .chip[data-filter="intermedio"]:hover {
        border-color: #fd7e14;
    }
    .chip[data-filter="avanzado"]:hover {
        border-color: #dc3545;
    }

    .search-bar {
        display: flex;
        align-items: center;
        background: var(--surface, #f8f9fa);
        border: 1px solid var(--border, #dee2e6);
        border-radius: 12px;
        padding: 0.4rem 0.8rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        margin-bottom: 0.5rem;
    }

    .search-bar:focus-within {
        border-color: var(--red, #e31e24);
        box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.12);
    }

    .prompt-char {
        font-family: 'JetBrains Mono', monospace;
        color: var(--muted, #6c757d);
        font-weight: 600;
        margin-right: 0.6rem;
        font-size: 1.1rem;
    }

    .search-bar input {
        border: none;
        background: transparent;
        padding: 0.5rem 0;
        font-size: 0.95rem;
        flex: 1;
        outline: none;
        font-family: 'Inter', sans-serif;
        color: var(--ink, #212529);
        width: 100%;
    }

    .search-bar input::placeholder {
        color: var(--muted, #adb5bd);
    }

    .results-count {
        font-size: 0.9rem;
        color: var(--muted, #6c757d);
        margin: 0.5rem 0 1.2rem 0;
        padding: 0.25rem 0;
        border-bottom: 1px dashed var(--border, #dee2e6);
    }

    .no-results {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted, #6c757d);
        font-style: italic;
        background: var(--surface, #f8f9fa);
        border-radius: 12px;
        margin-top: 1.5rem;
        border: 1px dashed var(--border, #dee2e6);
        display: none;
    }

    .no-results.visible {
        display: block;
    }

    .course-col {
        transition: opacity 0.25s ease, transform 0.25s ease;
        will-change: opacity, transform;
    }

    .course-col.hidden {
        opacity: 0;
        transform: scale(0.95);
        pointer-events: none;
        display: none !important; 
    }
</style>
<div class="container py-4">

    <h1 class="mb-4">Catálogo de Cursos</h1>

    <div class="search-bar">
        <span class="prompt-char">$</span>
        <input type="text" id="searchInput" placeholder="buscar curso — ej: python, react, sql...">
    </div>

    <div class="filters-wrapper">
        <div class="filters" id="filters">
            <div class="filter-group">
                <span class="filter-label">Nivel</span>
                <button class="chip active" data-filter="nivel" data-value="todos">Todos (<?= $total_cursos ?>)</button>
                <button class="chip" data-filter="nivel" data-value="principiante">Principiante</button>
                <button class="chip" data-filter="nivel" data-value="intermedio">Intermedio</button>
                <button class="chip" data-filter="nivel" data-value="avanzado">Avanzado</button>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Categoría</span>
                <button class="chip active" data-filter="categoria" data-value="todas">Todas</button>
                <?php foreach ($categorias as $categoria): ?>
                    <button class="chip" data-filter="categoria" data-value="<?= strtolower(htmlspecialchars($categoria['Nombre'])) ?>">
                        <?= htmlspecialchars($categoria['Nombre']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="results-count" id="resultsCount">
        mostrando <?= $total_cursos ?> de <?= $total_cursos ?> cursos
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4" id="courseGrid">
        <?php if ($total_cursos === 0): ?>
            <div class="col-12">
                <p class="text-muted">No hay cursos disponibles en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cursos as $curso): 
                $profesor_nombre_completo = trim(
                    ($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellidos'] ?? '')
                ) ?: 'Profesor';
                
                $nivel_bd = strtolower($curso['nivel'] ?? 'básico');
                if ($nivel_bd === 'básico' || $nivel_bd === 'basico') {
                    $nivel_filtro = 'principiante';
                } elseif ($nivel_bd === 'intermedio') {
                    $nivel_filtro = 'intermedio';
                } elseif ($nivel_bd === 'avanzado') {
                    $nivel_filtro = 'avanzado';
                } else {
                    $nivel_filtro = 'principiante';
                }
                
                $categoria_nombre = $curso['categoria_nombre'] ?? 'Sin categoría';
                $categoria_filtro = strtolower($categoria_nombre);
            ?>
                <div class="col course-col" 
                     data-level="<?= $nivel_filtro ?>" 
                     data-category="<?= htmlspecialchars($categoria_filtro) ?>"
                     data-name="<?= strtolower(htmlspecialchars($curso['Nombre'])) ?>">>
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
                            <div class="mb-2 small">
                                <p class="mb-1">
                                    <strong>Categoría:</strong> <span class="text-dark"><?= htmlspecialchars($categoria_nombre) ?></span>
                                </p>
                                <?php 
                                    $nivel_display = $curso['nivel'] ?? 'Básico';
                                ?>
                                <p class="mb-1">
                                    <strong>Nivel:</strong> <span class="text-dark"><?= htmlspecialchars($nivel_display) ?></span>
                                </p>
                                <p class="mb-0">
                                    <strong>Profesor:</strong> <span class="text-dark"><?= htmlspecialchars($profesor_nombre_completo) ?></span>
                                </p>
                            </div>
                            <div class="">
                                <span class="h5 mb-0 text-primary fw-bold">$<?= number_format($curso['Precio'] ?? 0, 2) ?></span>
                                <div>
                                    <a href="../Carrito/agregar.php?id=<?= $curso['ID'] ?>" class="btn btn-danger">
                                        <i class="bi bi-cart-plus"></i> Añadir al carrito
                                    </a>
                                        <a href="../Cursos_Usuario/contenido.php?id=<?= $curso['ID'] ?>" class="btn btn-primary">
                                            <i class="bi bi-eye"></i> Ver contenido
                                        </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="no-results" id="noResults">
        // no encontramos cursos que coincidan con tu búsqueda
    </div>

</div>

<script>
    (function() {
        const chips = document.querySelectorAll('.chip');
        const courseCols = document.querySelectorAll('.course-col');
        const searchInput = document.getElementById('searchInput');
        const resultsCount = document.getElementById('resultsCount');
        const noResults = document.getElementById('noResults');
        const totalCursos = <?= $total_cursos ?>;

        let activeFilters = {
            nivel: 'todos',
            categoria: 'todas'
        };

        function applyFilters() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            courseCols.forEach(col => {
                const level = col.dataset.level || 'principiante';
                const category = col.dataset.category || 'sin categoría';
                const name = col.dataset.name || '';
                
                const matchesLevel = activeFilters.nivel === 'todos' || level === activeFilters.nivel;
                const matchesCategory = activeFilters.categoria === 'todas' || category === activeFilters.categoria;
                const matchesQuery = name.includes(query);
                
                const show = matchesLevel && matchesCategory && matchesQuery;

                if (show) {
                    col.classList.remove('hidden');
                    visibleCount++;
                } else {
                    col.classList.add('hidden');
                }
            });

            resultsCount.textContent = `mostrando ${visibleCount} de ${totalCursos} cursos`;
            if (visibleCount === 0 && totalCursos > 0) {
                noResults.classList.add('visible');
            } else {
                noResults.classList.remove('visible');
            }
        }

        chips.forEach(chip => {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                const filterType = this.dataset.filter;
                const filterValue = this.dataset.value;

                chips.forEach(c => {
                    if (c.dataset.filter === filterType) {
                        c.classList.remove('active');
                    }
                });

                this.classList.add('active');
                activeFilters[filterType] = filterValue;
                applyFilters();
            });
        });

        searchInput.addEventListener('input', applyFilters);
        searchInput.addEventListener('keyup', applyFilters);

        applyFilters();
        document.addEventListener('DOMContentLoaded', applyFilters);
    })();
</script>

<?php include("../footer.php"); ?>