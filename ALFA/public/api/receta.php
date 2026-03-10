<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID requerido']);
    exit;
}

// Obtener receta
$stmt = $pdo->prepare("
    SELECT r.*, u.nombre as autor_nombre, u.rol as autor_rol
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Receta no encontrada']);
    exit;
}

// Obtener imágenes
$stmt = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden");
$stmt->execute([$id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
$receta['imagenes'] = $imagenes;

// Obtener comentarios
$stmt = $pdo->prepare("
    SELECT c.*, u.nombre as usuario_nombre
    FROM comentarios c
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.receta_id = ?
    ORDER BY c.fecha DESC
");
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'receta' => $receta, 'comentarios' => $comentarios]);
?>