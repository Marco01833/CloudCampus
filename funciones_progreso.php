<?php
function calcularProgresoCurso($conexion, $user_id, $curso_id) {
    $stmt = $conexion->prepare("SELECT ID FROM Cuestionarios WHERE IDCurso = ?");
    $stmt->execute([$curso_id]);
    $cuestionarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total_cuestionarios = count($cuestionarios);
    
    if ($total_cuestionarios == 0) {
        return 0;
    }
    $cuestionarios_completados = 0;
    $suma_notas = 0;
    foreach ($cuestionarios as $cuestionario_id) {
        $stmt = $conexion->prepare("
            SELECT Calificacion FROM IntentosCuestionario 
            WHERE IDUsuario = ? AND IDCuestionario = ? AND Estado = 'finalizado'
            ORDER BY Calificacion DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $cuestionario_id]);
        $intento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($intento && $intento['Calificacion'] !== null) {
            $cuestionarios_completados++;
            $suma_notas += $intento['Calificacion'];
        }
    }
    $progreso_completado = ($cuestionarios_completados / $total_cuestionarios) * 30;
    $progreso_nota = ($suma_notas / ($total_cuestionarios * 100)) * 70;
    $progreso = $progreso_completado + $progreso_nota;
    $progreso = min(round($progreso, 2), 100);
    return $progreso;
}
function actualizarProgresoCurso($conexion, $user_id, $curso_id) {
    if ($curso_id <= 0) {
        return 0;
    }
    $progreso = calcularProgresoCurso($conexion, $user_id, $curso_id);
    $stmt = $conexion->prepare("UPDATE Inscripciones SET progreso = ? WHERE IDUsuario = ? AND IDCurso = ?");
    $stmt->execute([$progreso, $user_id, $curso_id]);
    return $progreso;
}
?>