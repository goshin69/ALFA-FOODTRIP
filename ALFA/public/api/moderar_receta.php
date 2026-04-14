<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$receta_id = $_POST['receta_id'] ?? 0;
$accion = $_POST['accion'] ?? '';

if (!$receta_id || !in_array($accion, ['eliminar', 'revision'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción inválida']);
    exit;
}

try {
    if ($accion === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
        $stmt->execute([$receta_id]);
        echo json_encode(['ok' => true, 'mensaje' => 'Receta eliminada']);
    } elseif ($accion === 'revision') {
        $stmt = $pdo->prepare("UPDATE recetas SET estado = 2 WHERE id = ?");
        $stmt->execute([$receta_id]);
        echo json_encode(['ok' => true, 'mensaje' => 'Receta marcada para revisión']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
