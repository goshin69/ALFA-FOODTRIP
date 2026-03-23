<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/filtro_palabras.php';

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

if (empty($titulo) && empty($descripcion) && empty($_FILES['archivos']['name'][0])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No hay datos para guardar']);
    exit;
}

$pdo->beginTransaction();
try {
    if ($receta_id_temp) {
        $stmt = $pdo->prepare("SELECT id FROM recetas WHERE id = ? AND usuario_id = ? AND estado = 0");
        $stmt->execute([$receta_id_temp, $usuario_id]);
        if ($stmt->rowCount() === 0) throw new Exception('Borrador no encontrado o no autorizado');
        $stmt = $pdo->prepare("UPDATE recetas SET titulo = ?, descripcion = ?, fecha_publicacion = NOW() WHERE id = ?");
        $stmt->execute([$titulo, $descripcion, $receta_id_temp]);
        $receta_id = $receta_id_temp;
    } else {
        $stmt = $pdo->prepare("INSERT INTO recetas (titulo, descripcion, usuario_id, estado, fecha_publicacion) VALUES (?, ?, ?, 0, NOW())");
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

    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../uploads/comidas/' . $receta_id . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedVideoExtensions = ['mp4', 'avi', 'mov', 'mkv', 'webm'];
        $allowedVideoMimes = ['video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/x-matroska', 'video/webm', 'video/mpeg'];

        $total = count($_FILES['archivos']['name']);
        for ($i = 0; $i < $total; $i++) {
            $error = $_FILES['archivos']['error'][$i];
            if ($error !== UPLOAD_ERR_OK) continue;

            $tmpFile = $_FILES['archivos']['tmp_name'][$i];
            $originalName = $_FILES['archivos']['name'][$i];
            $mime = $_FILES['archivos']['type'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ((strpos($mime, 'image/') === 0 && in_array($extension, $allowedImageExtensions)) ||
                (in_array($mime, $allowedVideoMimes) && in_array($extension, $allowedVideoExtensions))) {
                $nuevoNombre = uniqid() . '_' . $usuario_id . '.' . $extension;
                $rutaBase = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                    $ruta = $rutaBase;
                    if (strpos($mime, 'image/') === 0 && function_exists('imagecreatefromstring')) {
                        $imgInfo = getimagesize($uploadDir . $nuevoNombre);
                        if ($imgInfo !== false && in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
                            switch ($imgInfo[2]) {
                                case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($uploadDir . $nuevoNombre); break;
                                case IMAGETYPE_PNG:  $img = imagecreatefrompng($uploadDir . $nuevoNombre); break;
                                case IMAGETYPE_GIF:  $img = imagecreatefromgif($uploadDir . $nuevoNombre); break;
                                case IMAGETYPE_WEBP: $img = imagecreatefromwebp($uploadDir . $nuevoNombre); break;
                                default: $img = false;
                            }
                            if ($img) {
                                $maxWidth = 1920;
                                $maxHeight = 1920;
                                $width = imagesx($img);
                                $height = imagesy($img);
                                $scale = min($maxWidth / $width, $maxHeight / $height, 1);
                                if ($scale < 1) {
                                    $newWidth = (int)($width * $scale);
                                    $newHeight = (int)($height * $scale);
                                    $imgResized = imagecreatetruecolor($newWidth, $newHeight);
                                    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                    imagedestroy($img);
                                    $img = $imgResized;
                                }
                                $webpPath = $uploadDir . pathinfo($nuevoNombre, PATHINFO_FILENAME) . '.webp';
                                imagewebp($img, $webpPath, 80);
                                imagedestroy($img);
                                unlink($uploadDir . $nuevoNombre);
                                $nuevoNombre = pathinfo($nuevoNombre, PATHINFO_FILENAME) . '.webp';
                                $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
                            }
                        }
                    }
                    $stmtCheck = $pdo->prepare("SELECT id FROM imagenes WHERE receta_id = ? AND ruta = ?");
                    $stmtCheck->execute([$receta_id, $ruta]);
                    if ($stmtCheck->rowCount() === 0) {
                        $stmtImg = $pdo->prepare("INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)");
                        $stmtImg->execute([$receta_id, $ruta, $i]);
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'receta_id' => $receta_id, 'borrador' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}