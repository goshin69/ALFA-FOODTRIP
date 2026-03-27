<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';

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

    $uploadDir = __DIR__ . '/../../uploads/comidas/' . $receta_id . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($receta_id_temp) {
        $stmtDel = $pdo->prepare("DELETE FROM imagenes WHERE receta_id = ?");
        $stmtDel->execute([$receta_id]);
        array_map('unlink', glob($uploadDir . "*.*"));
    }

    $archivosSubidos = [];
    $videoCount = 0;
    $imageCount = 0;
    $MAX_VIDEOS = 1;
    $MAX_IMAGES = 5;
    $MAX_FILE_SIZE = 100 * 1024 * 1024;
    $orden = 0;

    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedVideoExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
    $allowedVideoMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];

    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $total = count($_FILES['archivos']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['archivos']['tmp_name'][$i];
            $originalName = $_FILES['archivos']['name'][$i];
            $mime = $_FILES['archivos']['type'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileSize = $_FILES['archivos']['size'][$i];

            if ($fileSize > $MAX_FILE_SIZE) {
                throw new Exception("El archivo \"$originalName\" supera el límite de 100 MB");
            }

            if (strpos($mime, 'video/') === 0 && in_array($extension, $allowedVideoExtensions) && in_array($mime, $allowedVideoMimes)) {
                if ($videoCount >= $MAX_VIDEOS) throw new Exception("Solo se permite $MAX_VIDEOS video");
                $orden++;
                $nuevoNombre = "video_$orden." . $extension;
                $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                    $archivosSubidos[] = $ruta;
                    $videoCount++;
                } else throw new Exception("Error al guardar el video \"$originalName\"");
            }
            elseif (strpos($mime, 'image/') === 0 && in_array($extension, $allowedImageExtensions)) {
                if ($imageCount >= $MAX_IMAGES) throw new Exception("Solo se permiten $MAX_IMAGES imágenes");
                $orden++;
                $nuevoNombre = "img_$orden." . $extension;
                $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                    $archivosSubidos[] = $ruta;
                    $imageCount++;
                } else throw new Exception("Error al guardar la imagen \"$originalName\"");
            } else {
                throw new Exception("Formato de archivo no permitido: \"$originalName\"");
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