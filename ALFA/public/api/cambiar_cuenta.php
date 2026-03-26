<?php
header('Content-Type: application/json');
require_once '../../includes/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(['ok' => false, 'error' => 'Token no proporcionado']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ?");
$stmt->execute([$token]);
if ($stmt->fetch()) {
    setcookie('session_token', $token, time() + 86400 * 30, '/', '', false, true);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Token inválido']);
}