<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? 'list';

if ($action === 'read') {
    $notif_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($notif_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$notif_id, $usuario_id]);
    echo json_encode(['ok' => true]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, tipo, referencia_id, mensaje, leida, fecha FROM notificaciones WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 10");
$stmt->execute([$usuario_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
$stmt->execute([$usuario_id]);
$no_leidas = (int)$stmt->fetchColumn();

echo json_encode(['ok' => true, 'no_leidas' => $no_leidas, 'notificaciones' => $notificaciones]);