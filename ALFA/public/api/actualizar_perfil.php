<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = trim($_POST['nombre'] ?? '');
$biografia = trim($_POST['biografia'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;

if (empty($nombre)) {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
    exit;
}

$imagen_perfil = null;
if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['imagen_perfil'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $uploadDir = __DIR__ . '/../../uploads/perfiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $nuevoNombre = uniqid() . '_' . $usuario_id . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $nuevoNombre)) {
            $imagen_perfil = '/uploads/perfiles/' . $nuevoNombre;
        } else {
            echo json_encode(['ok' => false, 'error' => 'Error al guardar la imagen']);
            exit;
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'Formato de imagen no permitido']);
        exit;
    }
}

if ($imagen_perfil) {
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, biografia = ?, fecha_nacimiento = ?, imagen_perfil = ? WHERE id = ?");
    $stmt->execute([$nombre, $biografia, $fecha_nacimiento, $imagen_perfil, $usuario_id]);
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
    'imagen_perfil' => $usuario['imagen_perfil']
]);