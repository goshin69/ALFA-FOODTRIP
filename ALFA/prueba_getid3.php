<?php
require_once 'includes/getid3/getid3.php';

$getID3 = new getID3();

// --- ¡¡¡CAMBIA ESTA RUTA POR LA DE UN VIDEO DE PRUEBA EN TU SERVIDOR!!! ---
$rutaVideo = 'uploads/comidas/Fortnite 2025.04.09 - 23.45.09.20.Victoria.DVR.mp4';
// ---

$info = $getID3->analyze($rutaVideo);

if (isset($info['error'])) {
    echo "Error al analizar el archivo: " . implode(', ', $info['error']);
} else {
    echo "¡Análisis exitoso!<br>";
    echo "Duración: " . ($info['playtime_seconds'] ?? 'No disponible') . " segundos.<br>";
    echo "Resolución: " . ($info['video']['resolution_x'] ?? 'N/A') . "x" . ($info['video']['resolution_y'] ?? 'N/A') . "<br>";
    echo "Formato: " . ($info['fileformat'] ?? 'N/A') . "<br>";
}
?>