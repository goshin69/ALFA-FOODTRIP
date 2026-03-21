<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/filtro_palabras.php';

$getID3Path = __DIR__ . '/../../includes/getid3/getid3.php';
$getID3_disponible = file_exists($getID3Path);
if ($getID3_disponible) require_once $getID3Path;

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
$usuario_id = $_SESSION['usuario_id'];
$etiquetas_ids = $_POST['etiquetas'] ?? '';
$receta_id_temp = $_POST['receta_id_temp'] ?? null;

if (empty($titulo) || empty($descripcion)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Título y descripción son obligatorios']);
    exit;
}

if (contienePalabraProhibida($titulo) || contienePalabraProhibida($descripcion)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El título o descripción contiene palabras inapropiadas.']);
    exit;
}

$pdo->beginTransaction();
try {
    if ($receta_id_temp) {
        $stmt = $pdo->prepare("SELECT id FROM recetas WHERE id = ? AND usuario_id = ? AND estado = 0");
        $stmt->execute([$receta_id_temp, $usuario_id]);
        if ($stmt->rowCount() === 0) throw new Exception('Borrador no encontrado o no autorizado');
        $stmt = $pdo->prepare("UPDATE recetas SET titulo = ?, descripcion = ?, estado = 1, fecha_publicacion = NOW() WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $receta_id_temp]);
        $receta_id = $receta_id_temp;
    } else {
        $stmt = $pdo->prepare("INSERT INTO recetas (titulo, descripcion, usuario_id, estado, fecha_publicacion) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$titulo, $descripcion, $usuario_id]);
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

    $archivosSubidos = [];
    $videoCount = 0;
    $imageCount = 0;
    $MAX_VIDEOS = 1;
    $MAX_IMAGES = 5;

    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedVideoExtensions = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
    $allowedVideoMimes = ['video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/x-matroska', 'video/webm', 'video/mpeg'];

    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $total = count($_FILES['archivos']['name']);
        if ($receta_id_temp) {
            $stmtDel = $pdo->prepare("DELETE FROM imagenes WHERE receta_id = ?");
            $stmtDel->execute([$receta_id]);
            array_map('unlink', glob($uploadDir . "*.*"));
        }

        for ($i = 0; $i < $total; $i++) {
            $error = $_FILES['archivos']['error'][$i];
            if ($error !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['archivos']['tmp_name'][$i];
            $originalName = $_FILES['archivos']['name'][$i];
            $mime = $_FILES['archivos']['type'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (strpos($mime, 'video/') === 0) {
                if ($videoCount >= $MAX_VIDEOS) throw new Exception("Solo se permite $MAX_VIDEOS video");
            } elseif (strpos($mime, 'image/') === 0) {
                if ($imageCount >= $MAX_IMAGES) throw new Exception("Solo se permiten $MAX_IMAGES imágenes");
            }

            if (strpos($mime, 'image/') === 0 && in_array($extension, $allowedImageExtensions)) {
                $nuevoNombre = uniqid() . '_' . $usuario_id . '_' . time() . '.' . $extension;
                $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                    $archivosSubidos[] = $ruta;
                    $imageCount++;
                } else throw new Exception("Error al mover imagen: $originalName");
            } elseif (in_array($mime, $allowedVideoMimes) && in_array($extension, $allowedVideoExtensions)) {
                if ($getID3_disponible) {
                    $getID3 = new getID3;
                    $info = $getID3->analyze($tmpFile);
                    $duracion = $info['playtime_seconds'] ?? 0;
                    $ancho = $info['video']['resolution_x'] ?? 0;
                    $alto = $info['video']['resolution_y'] ?? 0;
                    if ($duracion > 2400) throw new Exception('El video no puede durar más de 40 minutos');
                    if ($ancho > 1920 || $alto > 1080) throw new Exception('El video no puede tener resolución mayor a 1080p');
                }
                $nuevoNombre = uniqid() . '_' . $usuario_id . '_' . time() . '.' . $extension;
                $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                    $archivosSubidos[] = $ruta;
                    $videoCount++;
                } else throw new Exception('Error al guardar el video');
            }
        }
    }

    if (empty($archivosSubidos)) throw new Exception('Debes subir al menos una imagen o video');

    $orden = 0;
    $stmtImg = $pdo->prepare("INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)");
    foreach ($archivosSubidos as $ruta) $stmtImg->execute([$receta_id, $ruta, $orden++]);

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