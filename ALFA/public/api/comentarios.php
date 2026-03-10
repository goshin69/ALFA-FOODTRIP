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

$receta_id = $_POST['receta_id'] ?? 0;
$puntuacion = $_POST['puntuacion'] ?? 0;
$comentario = trim($_POST['comentario'] ?? '');
$usuario_id = $_SESSION['usuario_id'];

if (!$receta_id || !$puntuacion || empty($comentario)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Campos incompletos']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO comentarios (receta_id, usuario_id, puntuacion, comentario, fecha) VALUES (?, ?, ?, ?, NOW())");
if ($stmt->execute([$receta_id, $usuario_id, $puntuacion, $comentario])) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar comentario']);
}
?>