<?php
include("../autenticacion.php");
include("../bd.php");

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$user_id = $_SESSION['user_id'];
$error = null;
$nombre_completo = '';
$curso_nombre = '';
$nota_final = 0;
$codigo = '';
$certificado_id = 0;

if ($curso_id <= 0) {
    $error = "ID de curso no válido.";
} else {
    $stmt = $conexion->prepare("SELECT Nombre FROM cursos WHERE ID = ?");
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$curso) {
        $error = "Curso no encontrado.";
    } else {
        $curso_nombre = $curso['Nombre'];
        
        $stmt = $conexion->prepare("SELECT ID FROM Cuestionarios WHERE IDCurso = ?");
        $stmt->execute([$curso_id]);
        $cuestionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($cuestionarios)) {
            $error = "Este curso no tiene cuestionarios. No se puede calcular la nota.";
        } else {
            $calificaciones = [];
            foreach ($cuestionarios as $cuestionario_id) {
                $stmt = $conexion->prepare("
                    SELECT Calificacion FROM IntentosCuestionario 
                    WHERE IDUsuario = ? AND IDCuestionario = ? AND Estado = 'finalizado'
                    ORDER BY Calificacion DESC LIMIT 1
                ");
                $stmt->execute([$user_id, $cuestionario_id]);
                $intento = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($intento && $intento['Calificacion'] !== null) {
                    $calificaciones[] = $intento['Calificacion'];
                }
            }
            
            if (empty($calificaciones)) {
                $error = "No has completado ningún cuestionario de este curso. Tu nota es 0%.";
            } else {
                $nota_final = array_sum($calificaciones) / count($calificaciones);
                
                $stmt = $conexion->prepare("SELECT ID FROM NotasCurso WHERE IDUsuario = ? AND IDCurso = ?");
                $stmt->execute([$user_id, $curso_id]);
                if ($stmt->fetch()) {
                    $stmt = $conexion->prepare("UPDATE NotasCurso SET NotaFinal = ?, FechaCalculo = NOW() WHERE IDUsuario = ? AND IDCurso = ?");
                    $stmt->execute([$nota_final, $user_id, $curso_id]);
                } else {
                    $stmt = $conexion->prepare("INSERT INTO NotasCurso (IDUsuario, IDCurso, NotaFinal, FechaCalculo) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $curso_id, $nota_final]);
                }
            }
        }
        
        if ($error) {
        } elseif ($nota_final < 70) {
            $error = "No has aprobado el curso. Tu nota final es: " . number_format($nota_final, 2) . "% (mínimo 70%).";
        } else {
            $stmt = $conexion->prepare("SELECT dp.Nombre, dp.Apellidos, u.Correo 
                                        FROM Usuarios u 
                                        LEFT JOIN DatosPersonales dp ON u.ID = dp.IDUsuario 
                                        WHERE u.ID = ?");
            $stmt->execute([$user_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_completo = trim(($estudiante['Nombre'] ?? '') . ' ' . ($estudiante['Apellidos'] ?? ''));
            if (empty($nombre_completo)) {
                $nombre_completo = $estudiante['Correo'] ?? 'Estudiante';
            }
            
            $stmt = $conexion->prepare("SELECT ID, codigo FROM certificados WHERE IDUsuario = ? AND IDCurso = ?");
            $stmt->execute([$user_id, $curso_id]);
            $cert_existente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cert_existente) {
                $certificado_id = $cert_existente['ID'];
                $codigo = $cert_existente['codigo'];
            } else {
                $codigo = strtoupper(bin2hex(random_bytes(8)));
                
                $pdf_dir = __DIR__ . '/pdf/';
                if (!file_exists($pdf_dir)) {
                    mkdir($pdf_dir, 0777, true);
                }
                
                $sql = "INSERT INTO certificados (IDUsuario, IDCurso, fecha_emision, codigo, archivo_pdf) 
                        VALUES (?, ?, NOW(), ?, 'pendiente')";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([$user_id, $curso_id, $codigo]);
                $certificado_id = $conexion->lastInsertId();
            }
        }
    }
}

include("../header.php");
?>

<div class="container py-4">
    <h1 class="mb-4">🎓 Certificado de Aprobación</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <a href="../Cursos_Usuario/contenido.php?id=<?= $curso_id ?>" class="btn btn-secondary">Volver al curso</a>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card shadow" id="certificadoCard">
                    <div class="card-body p-4">
                        <div class="text-center certificado">
                            <h1 class="h3 text-primary mb-3">🏆 CERTIFICADO DE APROBACIÓN</h1>
                            <p class="lead mb-2">Se otorga el presente certificado a</p>
                            <h2 class="h4 fw-bold text-primary my-3 border-bottom border-2 border-primary d-inline-block pb-2">
                                <?= htmlspecialchars($nombre_completo) ?>
                            </h2>
                            <p class="lead mb-2">por haber completado satisfactoriamente el curso</p>
                            <h3 class="h5 fw-bold text-dark my-3">
                                "<?= htmlspecialchars($curso_nombre) ?>"
                            </h3>
                            <p class="h6 text-success fw-bold">
                                Nota final: <?= number_format($nota_final, 2) ?>%
                            </p>
                            <p class="mt-3 text-muted small">
                                <small>Emitido el: <?= date('d/m/Y') ?></small>
                            </p>
                            <p class="mt-1 text-muted small">
                                <small>Código de verificación: <strong><?= $codigo ?></strong></small>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <button id="btnDescargarPDF" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    document.getElementById('btnDescargarPDF')?.addEventListener('click', function() {
        const elemento = document.querySelector('#certificadoCard');
        if (!elemento) return;

        const opt = {
            margin: 0.5,
            filename: 'certificado_<?= $curso_id ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(elemento).save();
    });
</script>

<style>
    .certificado {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 2px double #007bff;
        border-radius: 12px;
        padding: 20px;
        min-height: 250px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .certificado h1 {
        font-family: 'Georgia', serif;
        letter-spacing: 1px;
        font-size: 1.8rem !important;
    }
    .certificado .h4 {
        font-family: 'Georgia', serif;
        font-size: 2rem !important;
    }
    .certificado .h5 {
        font-family: 'Georgia', serif;
        font-size: 1.5rem !important;
    }
    .certificado .lead {
        font-size: 1.1rem !important;
        margin-bottom: 0.5rem !important;
    }
    .certificado .h6 {
        font-size: 1.1rem !important;
    }
    .certificado .small {
        font-size: 0.8rem !important;
    }
    .certificado .border-primary {
        border-color: #007bff !important;
        border-width: 2px !important;
    }
    @media print {
        .btn, .container .btn, .container .d-flex {
            display: none !important;
        }
        .certificado {
            border: 2px solid #000 !important;
        }
        .col-md-8 {
            max-width: 100% !important;
        }
        .row {
            margin: 0 !important;
        }
    }
</style>

<?php include("../footer.php"); ?>