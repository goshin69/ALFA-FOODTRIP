<?php
header('Content-Type: application/json');
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/ALFA/includes/database.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
$excludeIds = isset($_GET['exclude_ids']) ? array_map('intval', explode(',', $_GET['exclude_ids'])) : [];
$includeFollowing = isset($_GET['include_following']) ? (bool)$_GET['include_following'] : false;

$userId = $_SESSION['usuarioId'] ?? null;
$secciones = [];

if ($includeFollowing && $userId) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, u.nombre as autor_nombre, u.id as autor_id,
               (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen,
               r.tiempo_preparacion, r.dificultad,
               (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN seguidores s ON s.seguido_id = r.usuario_id
        WHERE s.seguidor_id = ? AND r.estado = 1
        ORDER BY r.fecha_publicacion DESC
        LIMIT 12
    ");
    $stmt->execute([$userId]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($recetas) > 0) {
        foreach ($recetas as &$rec) {
            $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
            $rec['descripcion'] = nl2br(htmlspecialchars($rec['descripcion']));
        }
        $secciones[] = [
            'nombre' => 'Siguiendo',
            'etiqueta_id' => 0,
            'recetas' => $recetas
        ];
    }
}

$etiquetas = [];
if ($userId) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.nombre, SUM(peso) as puntuacion
        FROM (
            SELECT re.etiqueta_id, 1 as peso FROM historial h
            JOIN recetas_etiquetas re ON re.receta_id = h.receta_id
            WHERE h.usuario_id = ? AND h.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT re.etiqueta_id, 2 as peso FROM me_gusta mg
            JOIN recetas_etiquetas re ON re.receta_id = mg.receta_id
            WHERE mg.usuario_id = ? AND mg.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT re.etiqueta_id, 3 as peso FROM favoritos f
            JOIN recetas_etiquetas re ON re.receta_id = f.receta_id
            WHERE f.usuario_id = ? AND f.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as interacciones
        JOIN etiquetas e ON e.id = interacciones.etiqueta_id
        GROUP BY e.id
        ORDER BY puntuacion DESC
        LIMIT 20
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($etiquetas)) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.nombre, COUNT(*) as puntuacion
        FROM recetas_etiquetas re
        JOIN etiquetas e ON e.id = re.etiqueta_id
        JOIN recetas r ON r.id = re.receta_id
        WHERE r.estado = 1
        GROUP BY e.id
        ORDER BY puntuacion DESC
        LIMIT 20
    ");
    $stmt->execute();
    $etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$etiquetasFiltradas = [];
foreach ($etiquetas as $et) {
    if (!in_array($et['id'], $excludeIds)) {
        $etiquetasFiltradas[] = $et;
    }
}

$seccionesRestantes = array_slice($etiquetasFiltradas, $offset, $limit);
foreach ($seccionesRestantes as $et) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, u.nombre as autor_nombre, u.id as autor_id,
               (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen,
               r.tiempo_preparacion, r.dificultad,
               (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN recetas_etiquetas re ON r.id = re.receta_id
        WHERE re.etiqueta_id = ? AND r.estado = 1
        ORDER BY r.fecha_publicacion DESC
        LIMIT 12
    ");
    $stmt->execute([$et['id']]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($recetas) > 0) {
        foreach ($recetas as &$rec) {
            $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
            $rec['descripcion'] = nl2br(htmlspecialchars($rec['descripcion']));
        }
        $secciones[] = [
            'nombre' => $et['nombre'],
            'etiqueta_id' => $et['id'],
            'recetas' => $recetas
        ];
    }
}

$hasMore = ($offset + $limit) < count($etiquetasFiltradas);
echo json_encode(['ok' => true, 'secciones' => $secciones, 'hasMore' => $hasMore]);