<?php
include("../bd.php");
include("../autenticacion.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso_id']) && isset($_POST['nuevo_estado'])) {
    $curso_id = (int)$_POST['curso_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    if ($nuevo_estado === 'Aprobado') {
        $stmt_contenido = $conexion->prepare("SELECT COUNT(*) as total FROM Contenido WHERE IDCurso = ?");
        $stmt_contenido->execute([$curso_id]);
        $resultado_contenido = $stmt_contenido->fetch(PDO::FETCH_ASSOC);
        $total_contenidos = $resultado_contenido['total'] ?? 0;
        
        $stmt_cuestionarios = $conexion->prepare("SELECT COUNT(*) as total FROM Cuestionarios WHERE IDCurso = ?");
        $stmt_cuestionarios->execute([$curso_id]);
        $resultado_cuestionarios = $stmt_cuestionarios->fetch(PDO::FETCH_ASSOC);
        $total_cuestionarios = $resultado_cuestionarios['total'] ?? 0;
        
        if ($total_contenidos < 4 || $total_cuestionarios < 2) {
            http_response_code(400);
            echo json_encode([
                'valido' => false,
                'mensaje' => "El curso debe tener mínimo 4 contenidos y 2 valoraciones. Actualmente tiene " . $total_contenidos . " contenidos y " . $total_cuestionarios . " valoraciones."
            ]);
            exit;
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'valido' => true,
        'mensaje' => 'Validación exitosa'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'valido' => false,
        'mensaje' => 'Solicitud inválida'
    ]);
}
?>
