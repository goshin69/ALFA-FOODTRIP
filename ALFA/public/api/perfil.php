<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$seguidor_id = $_SESSION['usuario_id'];
$seguido_id = $_POST['seguido_id'] ?? 0;
$action = $_POST['action'] ?? 'follow';

if (!$seguido_id || $seguido_id == $seguidor_id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

if ($action === 'follow') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $siguiendo = true;
} elseif ($action === 'unfollow') {
    $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $siguiendo = false;
} else {
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
$stmt->execute([$seguido_id]);
$seguidores = $stmt->fetchColumn();

echo json_encode(['ok' => true, 'siguiendo' => $siguiendo, 'seguidores' => $seguidores]);