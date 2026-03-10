<?php
session_start();
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

$nombre = trim($_POST['nombre'] ?? '');
if (empty($nombre)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El nombre de la etiqueta es obligatorio']);
    exit;
}

// Validar que no contenga groserías
if (contienePalabraProhibida($nombre)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La etiqueta contiene palabras inapropiadas. Por favor, elige otro nombre.']);
    exit;
}

// Verificar si ya existe
$stmt = $pdo->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
$stmt->execute([$nombre]);
if ($stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'La etiqueta ya existe']);
    exit;
}

// Insertar nueva etiqueta
$stmt = $pdo->prepare("INSERT INTO etiquetas (nombre, creada_por) VALUES (?, ?)");
if ($stmt->execute([$nombre, $_SESSION['usuario_id']])) {
    $id = $pdo->lastInsertId();
    echo json_encode(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al crear etiqueta']);
}
?>