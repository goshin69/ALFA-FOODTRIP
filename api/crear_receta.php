<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/ALFA/includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$preparacion = trim($_POST['preparacion'] ?? '');
$ingredientes = trim($_POST['ingredientes'] ?? '');
$dificultad = $_POST['dificultad'] ?? 'media';
$tiempo_preparacion = intval($_POST['tiempo_preparacion'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];
$etiquetas_ids = $_POST['etiquetas'] ?? '';
$receta_id_temp = $_POST['receta_id_temp'] ?? null;

if (empty($titulo) || empty($descripcion) || empty($preparacion) || empty($ingredientes)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Título, descripción, preparación e ingredientes son obligatorios']);
    exit;
}

$pdo->beginTransaction();
try {
    if ($receta_id_temp) {
        $stmt = $pdo->prepare("SELECT id FROM recetas WHERE id = ? AND usuario_id = ? AND estado = 0");
        $stmt->execute([$receta_id_temp, $usuario_id]);
        if ($stmt->rowCount() === 0) throw new Exception('Borrador no encontrado o no autorizado');
        $stmt = $pdo->prepare("UPDATE recetas SET titulo = ?, descripcion = ?, preparacion = ?, ingredientes = ?, dificultad = ?, tiempo_preparacion = ?, estado = 1, fecha_publicacion = NOW() WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $preparacion, $ingredientes, $dificultad, $tiempo_preparacion, $receta_id_temp]);
        $receta_id = $receta_id_temp;
    } else {
        $stmt = $pdo->prepare("INSERT INTO recetas (titulo, descripcion, preparacion, ingredientes, dificultad, tiempo_preparacion, usuario_id, estado, fecha_publicacion) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$titulo, $descripcion, $preparacion, $ingredientes, $dificultad, $tiempo_preparacion, $usuario_id]);
        $receta_id = $pdo->lastInsertId();
    }

    if (!empty($etiquetas_ids)) {
        $stmtDel = $pdo->prepare("DELETE FROM recetas_etiquetas WHERE receta_id = ?");
        $stmtDel->execute([$receta_id]);
        $ids_array = array_filter(explode(',', $etiquetas_ids), 'is_numeric');
        if (!empty($ids_array)) {
            $stmtEtiqueta = $pdo->prepare("INSERT INTO recetas_etiquetas (receta_id, etiqueta_id) VALUES (?, ?)");
            foreach ($ids_array as $etiqueta_id) $stmtEtiqueta->execute([$receta_id, $etiqueta_id]);
        }
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ALFA/public/uploads/comidas/' . $receta_id . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($receta_id_temp) {
        $stmtDel = $pdo->prepare("DELETE FROM imagenes WHERE receta_id = ?");
        $stmtDel->execute([$receta_id]);
        array_map('unlink', glob($uploadDir . "*.*"));
    }

    function optimizarImagen($origen, $destino, $calidad = 85, $maxAncho = 1200) {
        $info = getimagesize($origen);
        if (!$info) return false;
        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg': $img = imagecreatefromjpeg($origen); break;
            case 'image/png':
                $img = imagecreatefrompng($origen);
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                break;
            case 'image/gif': $img = imagecreatefromgif($origen); break;
            case 'image/webp': $img = imagecreatefromwebp($origen); break;
            default: return false;
        }
        $ancho = $info[0];
        $alto = $info[1];
        if ($ancho > $maxAncho) {
            $nuevoAlto = ($maxAncho * $alto) / $ancho;
            $nueva = imagecreatetruecolor($maxAncho, $nuevoAlto);
            if ($mime == 'image/png') {
                imagealphablending($nueva, false);
                imagesavealpha($nueva, true);
                $transparent = imagecolorallocatealpha($nueva, 255, 255, 255, 127);
                imagefilledrectangle($nueva, 0, 0, $maxAncho, $nuevoAlto, $transparent);
            }
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, $maxAncho, $nuevoAlto, $ancho, $alto);
            imagedestroy($img);
            $img = $nueva;
        }
        imagewebp($img, $destino, $calidad);
        imagedestroy($img);
        return true;
    }

    $archivosSubidos = [];
    $videoCount = 0;
    $imageCount = 0;
    $orden = 0;
    $MAX_VIDEOS = 1;
    $MAX_IMAGES = 5;
    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $total = count($_FILES['archivos']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['archivos']['tmp_name'][$i];
            $originalName = $_FILES['archivos']['name'][$i];
            $mime = $_FILES['archivos']['type'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileSize = $_FILES['archivos']['size'][$i];

            if ($fileSize > 100 * 1024 * 1024) {
                throw new Exception("El archivo \"$originalName\" supera el límite de 100 MB");
            }

            if (strpos($mime, 'image/') === 0 && in_array($extension, $allowedImageExtensions)) {
                if ($imageCount >= $MAX_IMAGES) throw new Exception("Solo se permiten $MAX_IMAGES imágenes");
                $orden++;
                $nuevoNombre = "img_$orden.webp";
                $rutaDestino = $uploadDir . $nuevoNombre;

                $tmpOriginal = $uploadDir . 'temp_img_' . uniqid() . '.' . $extension;
                if (!move_uploaded_file($tmpFile, $tmpOriginal)) {
                    throw new Exception("Error al mover temporal de imagen");
                }
                if (optimizarImagen($tmpOriginal, $rutaDestino, 85, 1200)) {
                    unlink($tmpOriginal);
                    $archivosSubidos[] = 'uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                    $imageCount++;
                } else {
                    unlink($tmpOriginal);
                    throw new Exception("Error al optimizar imagen \"$originalName\"");
                }
            }
            elseif ($mime === 'video/mp4' && $extension === 'mp4') {
                if ($videoCount >= $MAX_VIDEOS) throw new Exception("Solo se permite $MAX_VIDEOS video");
                $orden++;
                $nuevoNombre = "video_$orden.mp4";
                $rutaDestino = $uploadDir . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $rutaDestino)) {
                    $archivosSubidos[] = 'uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                    $videoCount++;
                } else {
                    throw new Exception("Error al guardar el video \"$originalName\"");
                }
            }
            else {
                throw new Exception("Formato no permitido. Solo imágenes (JPG, PNG, GIF, WebP) y video MP4.");
            }
        }
    }

    if (empty($archivosSubidos)) {
        throw new Exception('Debes subir al menos una imagen o video');
    }

    $ordenDB = 0;
    $stmtImg = $pdo->prepare("INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)");
    foreach ($archivosSubidos as $ruta) {
        $stmtImg->execute([$receta_id, $ruta, $ordenDB++]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'receta_id' => $receta_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    if (isset($uploadDir) && is_dir($uploadDir) && !$receta_id_temp) {
        array_map('unlink', glob($uploadDir . "*.*"));
        rmdir($uploadDir);
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}