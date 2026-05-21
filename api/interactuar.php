<?php
// api/interactuar.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$receta_id = isset($_POST['receta_id']) ? (int)$_POST['receta_id'] : 0;
$accion = $_POST['accion'] ?? '';

if ($receta_id <= 0 || !in_array($accion, ['like', 'favorite', 'history'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

try {
    switch ($accion) {
        case 'like':
            $stmt = $pdo->prepare("SELECT 1 FROM me_gusta WHERE usuario_id = ? AND receta_id = ?");
            $stmt->execute([$usuario_id, $receta_id]);
            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare("DELETE FROM me_gusta WHERE usuario_id = ? AND receta_id = ?");
                $stmt->execute([$usuario_id, $receta_id]);
                $activo = false;
            } else {
                $stmt = $pdo->prepare("INSERT INTO me_gusta (usuario_id, receta_id) VALUES (?, ?)");
                $stmt->execute([$usuario_id, $receta_id]);
                $activo = true;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM me_gusta WHERE receta_id = ?");
            $stmt->execute([$receta_id]);
            $total = $stmt->fetchColumn();
            echo json_encode(['ok' => true, 'activo' => $activo, 'total' => $total]);
            break;

        case 'favorite':
            $stmt = $pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
            $stmt->execute([$usuario_id, $receta_id]);
            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
                $stmt->execute([$usuario_id, $receta_id]);
                $activo = false;
            } else {
                $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, receta_id) VALUES (?, ?)");
                $stmt->execute([$usuario_id, $receta_id]);
                $activo = true;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE receta_id = ?");
            $stmt->execute([$receta_id]);
            $total = $stmt->fetchColumn();
            echo json_encode(['ok' => true, 'activo' => $activo, 'total' => $total]);
            break;

        case 'history':
            $stmt = $pdo->prepare("INSERT INTO historial (usuario_id, receta_id, fecha) VALUES (?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE fecha = NOW()");
            $stmt->execute([$usuario_id, $receta_id]);
            echo json_encode(['ok' => true]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
}