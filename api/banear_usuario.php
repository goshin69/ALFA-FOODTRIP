<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo administradores']);
    exit;
}

$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
if ($usuario_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

$pdo->prepare("UPDATE usuarios SET rol = 'baneado' WHERE id = ?")->execute([$usuario_id]);
$pdo->prepare("UPDATE strikes SET estado = 'resuelto' WHERE usuario_id = ? AND estado = 'activo'")->execute([$usuario_id]);
echo json_encode(['ok' => true]);