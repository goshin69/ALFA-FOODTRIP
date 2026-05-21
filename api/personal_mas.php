<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/database.php';

if (!isset($_SESSION['usuarioId'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$userId = $_SESSION['usuarioId'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;

$stmt = $pdo->prepare("
    SELECT et.id, et.nombre, COUNT(*) as cnt
    FROM (
        SELECT receta_id FROM me_gusta WHERE usuario_id = ?
        UNION ALL
        SELECT receta_id FROM favoritos WHERE usuario_id = ?
        UNION ALL
        SELECT receta_id FROM historial WHERE usuario_id = ?
    ) AS interacciones
    JOIN recetas_etiquetas re ON interacciones.receta_id = re.receta_id
    JOIN etiquetas et ON re.etiqueta_id = et.id
    GROUP BY et.id
    ORDER BY cnt DESC
    LIMIT ?, ?
");
$stmt->execute([$userId, $userId, $userId, $offset, $limit]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($tags)) {
    $stmtFallback = $pdo->prepare("SELECT id, nombre FROM etiquetas ORDER BY id LIMIT ?, ?");
    $stmtFallback->execute([$offset, $limit]);
    $tags = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
}

$secciones = [];
foreach ($tags as $tag) {
    $stmtRec = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, 
               u.nombre as autor_nombre, u.id as autor_id,
               (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen,
               r.tiempo_preparacion, r.dificultad,
               (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN recetas_etiquetas re ON r.id = re.receta_id
        WHERE re.etiqueta_id = ? AND r.estado = 1
        ORDER BY r.fecha_publicacion DESC, r.vistas DESC
        LIMIT 10
    ");
    $stmtRec->execute([$tag['id']]);
    $recetas = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recetas as &$rec) {
        $rec['descripcion'] = nl2br(htmlspecialchars($rec['descripcion']));
        if (!$rec['imagen']) $rec['imagen'] = 'imageness/default_receta.jpg';
    }
    if (!empty($recetas)) {
        $secciones[] = [
            'nombre' => $tag['nombre'],
            'etiqueta_id' => $tag['id'],
            'recetas' => $recetas
        ];
    }
}

$hasMore = ($offset + $limit) < count($tags);
$nextOffset = $hasMore ? $offset + $limit : null;

echo json_encode([
    'ok' => true,
    'sections' => $secciones,
    'has_more' => $hasMore,
    'next_offset' => $nextOffset
]);