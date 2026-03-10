<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autenticado']); exit; }
$receta_id = isset($_POST['receta_id']) ? (int)$_POST['receta_id'] : 0;
$comentario = trim($_POST['comentario'] ?? '');
$puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : -1;
if ($receta_id <= 0 || $comentario === '' || $puntuacion < 0 || $puntuacion > 5) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Datos inválidos']); exit; }
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare('INSERT INTO comentarios (receta_id, usuario_id, comentario, puntuacion) VALUES (?, ?, ?, ?)');
if ($stmt->execute([$receta_id, $usuario_id, $comentario, $puntuacion])) { echo json_encode(['ok'=>true]); exit; }
http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al guardar comentario']);
