<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? '';
if ($nombre === '' || $email === '' || $password === '' || $rol === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Campos incompletos']); exit; }
$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'Email ya registrado']); exit; }
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)');
if ($stmt->execute([$nombre, $email, $hash, $rol])) { echo json_encode(['ok'=>true]); exit; }
http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al registrar']);
