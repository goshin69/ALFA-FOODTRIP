<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autenticado']); exit; }
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
if ($titulo === '' || $descripcion === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Campos obligatorios']); exit; }
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare('INSERT INTO recetas (usuario_id, titulo, descripcion) VALUES (?, ?, ?)');
if (!$stmt->execute([$usuario_id, $titulo, $descripcion])) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Error al crear receta']); exit; }
$receta_id = $pdo->lastInsertId();
if (!empty($_FILES['imagenes']['name'][0])) {
    $carpeta = __DIR__ . '/../uploads/recetas/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    $total = count($_FILES['imagenes']['name']);
    for ($i=0;$i<$total;$i++) {
        if (empty($_FILES['imagenes']['name'][$i])) continue;
        $orig = $_FILES['imagenes']['name'][$i];
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $uniq = uniqid() . '_' . time() . '.' . $ext;
        $ruta_rel = 'uploads/recetas/' . $uniq;
        if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], __DIR__ . '/../' . $ruta_rel)) {
            $stmt_img = $pdo->prepare('INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)');
            $stmt_img->execute([$receta_id, $ruta_rel, $i]);
        }
    }
}

// manejar video opcional
if (!empty($_FILES['video']['name'])) {
    $carpeta = __DIR__ . '/../uploads/recetas/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    $orig = $_FILES['video']['name'];
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $uniq = uniqid() . '_' . time() . '.' . $ext;
    $ruta_rel = 'uploads/recetas/' . $uniq;
    if (move_uploaded_file($_FILES['video']['tmp_name'], __DIR__ . '/../' . $ruta_rel)) {
        // insertar como 'imagen' para reutilizar tabla; el front-end detectará por extensión
        $stmt_img = $pdo->prepare('INSERT INTO imagenes (receta_id, ruta, orden) VALUES (?, ?, ?)');
        // usar orden igual al total actual (append al final)
        $orden = isset($total) ? $total : 0;
        $stmt_img->execute([$receta_id, $ruta_rel, $orden]);
    }
}
echo json_encode(['ok'=>true,'receta_id'=>$receta_id]);
