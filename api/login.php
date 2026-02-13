<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;
if (empty($email) || empty($password)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Campos incompletos']); exit; }
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if ($usuario && password_verify($password, $usuario['password'])) {
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    echo json_encode(['ok'=>true,'usuario'=>['id'=>$usuario['id'],'nombre'=>$usuario['nombre'],'rol'=>$usuario['rol']]]);
    exit;
}
http_response_code(401);
echo json_encode(['ok'=>false,'error'=>'Email o contraseña incorrectos']);
exit;
