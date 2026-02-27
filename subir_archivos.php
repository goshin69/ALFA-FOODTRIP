<?php
// Verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    die('No autorizado');
}

// Validar que se hayan enviado archivos
if (!isset($_FILES['files'])) {
    die('No se enviaron archivos');
}

// Directorio de destino
$uploadDir = __DIR__ . '/../uploads/recetas/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tipos permitidos
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/avif' => 'avif',
    'image/webp' => 'webp',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv'
];

$maxFileSize = 100 * 1024 * 1024; // 100MB
$uploadedCount = 0;
$errors = [];

foreach ($_FILES['files']['tmp_name'] as $key => $tmpFile) {
    $fileName = $_FILES['files']['name'][$key];
    $fileSize = $_FILES['files']['size'][$key];
    $fileMime = $_FILES['files']['type'][$key];
    $fileError = $_FILES['files']['error'][$key];

    // Validaciones
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "$fileName: Error en la subida";
        continue;
    }

    if ($fileSize > $maxFileSize) {
        $errors[] = "$fileName: Archivo demasiado grande (máximo 100MB)";
        continue;
    }

    if (!isset($allowedMimes[$fileMime])) {
        $errors[] = "$fileName: Tipo de archivo no permitido";
        continue;
    }

    // Generar nombre único
    $extension = $allowedMimes[$fileMime];
    $newFileName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $newFileName;

    // Mover archivo
    if (move_uploaded_file($tmpFile, $uploadPath)) {
        $uploadedCount++;
    } else {
        $errors[] = "$fileName: Error al guardar el archivo";
    }
}

// Mostrar resultado
if ($uploadedCount > 0) {
    echo "Archivos subidos correctamente: $uploadedCount";
} else {
    echo "error: " . implode(', ', $errors);
}
?>
