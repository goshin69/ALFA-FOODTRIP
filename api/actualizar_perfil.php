<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$biografia = isset($_POST['biografia']) ? trim($_POST['biografia']) : '';
$fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : '';
$imagen_perfil = isset($_FILES['imagen_perfil']) ? $_FILES['imagen_perfil'] : null;

if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
    exit;
}

if (!empty($fecha_nacimiento)) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    if (!$d || $d->format('Y-m-d') !== $fecha_nacimiento) {
        echo json_encode(['ok' => false, 'error' => 'Fecha de nacimiento no válida']);
        exit;
    }
}

$nueva_ruta_imagen = null;

if ($imagen_perfil && $imagen_perfil['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $imagen_perfil['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        echo json_encode(['ok' => false, 'error' => 'Formato de imagen no permitido']);
        exit;
    }
    if ($imagen_perfil['size'] > 5 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'La imagen supera los 5 MB']);
        exit;
    }

    // Crear recurso desde el archivo original
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($imagen_perfil['tmp_name']); break;
        case 'image/png':  $img = imagecreatefrompng($imagen_perfil['tmp_name']); break;
        case 'image/gif':  $img = imagecreatefromgif($imagen_perfil['tmp_name']); break;
        case 'image/webp': $img = imagecreatefromwebp($imagen_perfil['tmp_name']); break;
        default: $img = false;
    }

    if (!$img) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo procesar la imagen']);
        exit;
    }

    // Redimensionar a 300x300 (recorte central)
    $ancho = imagesx($img);
    $alto = imagesy($img);
    $lado = min($ancho, $alto);
    $src_x = ($ancho - $lado) / 2;
    $src_y = ($alto - $lado) / 2;

    $thumb = imagecreatetruecolor(300, 300);
    imagecopyresampled($thumb, $img, 0, 0, (int)$src_x, (int)$src_y, 300, 300, $lado, $lado);
    imagedestroy($img);

    // Guardar como WebP
    $directorio = __DIR__ . '/../uploads/perfiles/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombre_archivo = 'perfil_' . $usuario_id . '.webp';
    $ruta_completa = $directorio . $nombre_archivo;

    // Eliminar cualquier imagen de perfil anterior del usuario (sin importar extensión)
    $patron = $directorio . 'perfil_' . $usuario_id . '.*';
    foreach (glob($patron) as $antiguo) {
        if (is_file($antiguo)) {
            unlink($antiguo);
        }
    }

    if (imagewebp($thumb, $ruta_completa, 80)) {
        $nueva_ruta_imagen = 'uploads/perfiles/' . $nombre_archivo;
    } else {
        imagedestroy($thumb);
        echo json_encode(['ok' => false, 'error' => 'Error al guardar la imagen webp']);
        exit;
    }
    imagedestroy($thumb);
}

// Actualizar base de datos
try {
    if ($nueva_ruta_imagen) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, biografia = ?, fecha_nacimiento = ?, imagen_perfil = ? WHERE id = ?");
        $stmt->execute([$nombre, $biografia, $fecha_nacimiento, $nueva_ruta_imagen, $usuario_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, biografia = ?, fecha_nacimiento = ? WHERE id = ?");
        $stmt->execute([$nombre, $biografia, $fecha_nacimiento, $usuario_id]);
    }

    $stmt = $pdo->prepare("SELECT nombre, biografia, fecha_nacimiento, imagen_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'nombre' => $usuario['nombre'],
        'biografia' => $usuario['biografia'],
        'fecha_nacimiento' => $usuario['fecha_nacimiento'],
        'imagen_perfil' => $usuario['imagen_perfil'] ? '/ALFA/public/' . $usuario['imagen_perfil'] : null
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al actualizar perfil']);
}