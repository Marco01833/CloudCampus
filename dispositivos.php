<?php
function obtenerNombreDispositivo($userAgent) {
    if (strpos($userAgent, 'Firefox') !== false) {
        $navegador = 'Firefox';
    } elseif (strpos($userAgent, 'Edg') !== false) {
        $navegador = 'Edge';
    } elseif (strpos($userAgent, 'OPR') !== false || strpos($userAgent, 'Opera') !== false) {
        $navegador = 'Opera';
    } elseif (strpos($userAgent, 'Chrome') !== false) {
        $navegador = 'Chrome';
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $navegador = 'Safari';
    } else {
        $navegador = 'Navegador desconocido';
    }

    if (strpos($userAgent, 'Windows NT') !== false) {
        $so = 'Windows';
    } elseif (strpos($userAgent, 'Mac OS X') !== false) {
        $so = 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $so = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $so = 'Android';
    } elseif (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $so = 'iOS';
    } else {
        $so = 'SO desconocido';
    }

    return $navegador . ' (' . $so . ')';
}
?>