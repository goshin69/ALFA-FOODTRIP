<?php
header('Content-Type: application/json');
require_once '../../includes/database.php';

$stmt = $pdo->prepare("SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, u.nombre as autor_nombre, u.id as autor_id, 
                              (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen
                       FROM recetas r
                       JOIN usuarios u ON r.usuario_id = u.id
                       WHERE r.estado = 1
                       ORDER BY r.fecha_publicacion DESC
                       LIMIT 20");
$stmt->execute();
$recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recetas as &$receta) {
    $receta['descripcion'] = nl2br(htmlspecialchars($receta['descripcion']));
    $receta['imagen'] = $receta['imagen'] ?: 'imageness/default_receta.jpg';
}

echo json_encode(['ok' => true, 'recetas' => $recetas]);