<?php
header('Content-Type: application/json');
require_once '../../includes/database.php';

$token = $_COOKIE['session_token'] ?? '';
if (!$token) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$stmt = $pdo->prepare("SELECT u.id, u.nombre, u.imagen_perfil, s.session_token 
                       FROM usuarios u 
                       JOIN user_sessions s ON u.id = s.user_id 
                       WHERE u.email = (SELECT email FROM usuarios u2 JOIN user_sessions s2 ON u2.id = s2.user_id WHERE s2.session_token = ?)");
$stmt->execute([$token]);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'cuentas' => $cuentas]);