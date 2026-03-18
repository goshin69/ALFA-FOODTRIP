<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

$logFile = __DIR__ . '/debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Inicio de crear_receta.php\n", FILE_APPEND);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/filtro_palabras.php';

$getID3Path = __DIR__ . '/../../includes/getid3/getid3.php';
if (file_exists($getID3Path)) {
    require_once $getID3Path;
    $getID3_disponible = true;
} else {
    $getID3_disponible = false;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ADVERTENCIA: getID3 no encontrado\n", FILE_APPEND);
}

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Includes cargados\n", FILE_APPEND);

if (!isset($_SESSION['usuario_id'])) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] No autorizado\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Método no permitido\n", FILE_APPEND);
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Usuario ID: " . $_SESSION['usuario_id'] . "\n", FILE_APPEND);

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id = $_SESSION['usuario_id'];
$etiquetas_ids = $_POST['etiquetas'] ?? ''; // string de IDs separados por coma

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Título: $titulo, Descripción: $descripcion, Etiquetas: $etiquetas_ids\n", FILE_APPEND);

if (empty($titulo) || empty($descripcion)) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Campos incompletos\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Título y descripción son obligatorios']);
    exit;
}

if (contienePalabraProhibida($titulo) || contienePalabraProhibida($descripcion)) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Palabras prohibidas detectadas\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El título o descripción contiene palabras inapropiadas. Por favor, edita tu contenido.']);
    exit;
}

file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Files: " . print_r($_FILES, true) . "\n", FILE_APPEND);

$pdo->beginTransaction();
try {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Insertando receta\n", FILE_APPEND);
    $stmt = $pdo->prepare("INSERT INTO recetas (titulo, descripcion, usuario_id, fecha_publicacion) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$titulo, $descripcion, $usuario_id]);
    $receta_id = $pdo->lastInsertId();
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Receta ID: $receta_id\n", FILE_APPEND);

    // Procesar etiquetas
    if (!empty($etiquetas_ids)) {
        $ids_array = array_filter(explode(',', $etiquetas_ids), 'is_numeric');
        if (!empty($ids_array)) {
            $stmtEtiqueta = $pdo->prepare("INSERT INTO recetas_etiquetas (receta_id, etiqueta_id) VALUES (?, ?)");
            foreach ($ids_array as $etiqueta_id) {
                $stmtEtiqueta->execute([$receta_id, $etiqueta_id]);
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Etiqueta $etiqueta_id asociada\n", FILE_APPEND);
            }
        }
    }

    $uploadDir = __DIR__ . '/../../uploads/comidas/' . $receta_id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Carpeta creada: $uploadDir\n", FILE_APPEND);
    }

    $imagenesSubidas = [];

    if (!empty($_FILES['imagenes']['name'][0])) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Procesando imágenes...\n", FILE_APPEND);
        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmpFile) {
            if ($_FILES['imagenes']['error'][$key] !== UPLOAD_ERR_OK) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error en imagen $key: " . $_FILES['imagenes']['error'][$key] . "\n", FILE_APPEND);
                continue;
            }
            $nombre = $_FILES['imagenes']['name'][$key];
            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Extensión no permitida: $extension\n", FILE_APPEND);
                continue;
            }
            $nuevoNombre = uniqid() . '.' . $extension;
            $ruta = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
            if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                $imagenesSubidas[] = $ruta;
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Imagen guardada: $ruta\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error al mover imagen: $nombre\n", FILE_APPEND);
            }
        }
    }

    if (!empty($_FILES['video']['name'])) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Procesando video...\n", FILE_APPEND);
        if ($_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['video']['tmp_name'];
            $nombre = $_FILES['video']['name'];
            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if ($extension !== 'mp4') {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Extensión no permitida: $extension\n", FILE_APPEND);
                throw new Exception('Solo se permiten archivos MP4');
            }

            if ($getID3_disponible) {
                $getID3 = new getID3;
                $archivoInfo = $getID3->analyze($tmpFile);
                $duracion = $archivoInfo['playtime_seconds'] ?? 0;
                $ancho = $archivoInfo['video']['resolution_x'] ?? 0;
                $alto = $archivoInfo['video']['resolution_y'] ?? 0;

                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Duración: $duracion, Resolución: {$ancho}x{$alto}\n", FILE_APPEND);

                if ($duracion > 2400) {
                    throw new Exception('El video no puede durar más de 40 minutos');
                }

                if ($ancho > 1920 || $alto > 1080) {
                    throw new Exception('El video no puede tener resolución mayor a 1080p');
                }
            } else {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] getID3 no disponible, omitiendo validación\n", FILE_APPEND);
            }

            $nuevoNombre = uniqid() . '.mp4';
            $rutaVideo = '/uploads/comidas/' . $receta_id . '/' . $nuevoNombre;
            if (move_uploaded_file($tmpFile, $uploadDir . $nuevoNombre)) {
                $imagenesSubidas[] = $rutaVideo;
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Video guardado: $rutaVideo\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error al mover video: $nombre\n", FILE_APPEND);
                throw new Exception('Error al guardar el video');
            }
        } else {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Error en video: " . $_FILES['video']['error'] . "\n", FILE_APPEND);
        }
    }

    $orden = 0;
    $stmtImg = $pdo->prepare("INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)");
    foreach ($imagenesSubidas as $ruta) {
        $stmtImg->execute([$receta_id, $ruta, $orden++]);
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Ruta guardada en BD: $ruta\n", FILE_APPEND);
    }

    $pdo->commit();
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Commit exitoso\n", FILE_APPEND);
    echo json_encode(['ok' => true, 'receta_id' => $receta_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND);
    if (isset($uploadDir) && is_dir($uploadDir)) {
        array_map('unlink', glob("$uploadDir/*.*"));
        rmdir($uploadDir);
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Carpeta eliminada por error\n", FILE_APPEND);
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>