<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID inválido']); exit; }
$stmt = $pdo->prepare('SELECT r.*, u.nombre as autor_nombre, u.rol as autor_rol FROM recetas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.id = ?');
$stmt->execute([$id]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$receta) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Receta no encontrada']); exit; }
$stmt_img = $pdo->prepare('SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden');
$stmt_img->execute([$id]);
$imagenes = $stmt_img->fetchAll(PDO::FETCH_COLUMN);
$stmt_com = $pdo->prepare('SELECT c.*, u.nombre AS usuario_nombre FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.receta_id = ? ORDER BY c.fecha DESC');
$stmt_com->execute([$id]);
$comentarios = $stmt_com->fetchAll(PDO::FETCH_ASSOC);
$suma = 0;
if (count($comentarios) > 0) { $suma = array_sum(array_column($comentarios, 'puntuacion')); $promedio = round($suma / count($comentarios), 1); } else { $promedio = 0; }
echo json_encode(['ok'=>true,'receta'=>$receta,'imagenes'=>$imagenes,'comentarios'=>$comentarios,'promedio'=>$promedio], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
