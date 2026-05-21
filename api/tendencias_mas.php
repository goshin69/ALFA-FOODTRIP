<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/ALFA/includes/database.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;

$stmt = $pdo->prepare("
    SELECT e.id, e.nombre,
           (SUM(COALESCE(mg.semana_likes, 0)) + SUM(COALESCE(vu.semana_vistas, 0))) AS puntuacion
    FROM etiquetas e
    JOIN recetas_etiquetas re ON re.etiqueta_id = e.id
    JOIN recetas r ON r.id = re.receta_id
    LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_likes FROM me_gusta WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) mg ON mg.receta_id = r.id
    LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_vistas FROM vistas_unicas WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) vu ON vu.receta_id = r.id
    WHERE r.estado = 1
    GROUP BY e.id
    ORDER BY puntuacion DESC
    LIMIT :offset, :limit
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];
foreach ($etiquetas as $etiqueta) {
    $stmt2 = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.tiempo_preparacion,
               (SELECT MIN(i.ruta) FROM imagenes i WHERE i.receta_id = r.id) as imagen,
               u.nombre as autor_nombre, u.id as autor_id,
               (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
        FROM recetas r
        JOIN recetas_etiquetas re ON re.receta_id = r.id
        JOIN usuarios u ON u.id = r.usuario_id
        WHERE re.etiqueta_id = ? AND r.estado = 1
        ORDER BY (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) DESC
        LIMIT 12
    ");
    $stmt2->execute([$etiqueta['id']]);
    $recetas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recetas as &$rec) {
        $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
    }
    $resultados[] = ['nombre' => $etiqueta['nombre'], 'recetas' => $recetas];
}
echo json_encode(['ok' => true, 'secciones' => $resultados]);