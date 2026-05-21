<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

if (!isset($_GET['user_id']) || !isset($_GET['tipo'])) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros faltantes']);
    exit;
}

$user_id = (int)$_GET['user_id'];
$tipo = $_GET['tipo'];

if (!in_array($tipo, ['seguidores', 'siguiendo'])) {
    echo json_encode(['ok' => false, 'error' => 'Tipo no válido']);
    exit;
}

try {
    if ($tipo === 'seguidores') {
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.imagen_perfil FROM usuarios u
                               JOIN seguidores s ON u.id = s.seguidor_id
                               WHERE s.seguido_id = ? ORDER BY s.fecha DESC LIMIT 50");
    } else {
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.imagen_perfil FROM usuarios u
                               JOIN seguidores s ON u.id = s.seguido_id
                               WHERE s.seguidor_id = ? ORDER BY s.fecha DESC LIMIT 50");
    }
    $stmt->execute([$user_id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
}