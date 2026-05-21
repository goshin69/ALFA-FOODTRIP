<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/ALFA/includes/database.php';
session_start();

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
$usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;

function obtenerEtiquetasPreferidas($pdo, $uid, $total) {
    if ($uid <= 0) {
        $stmt = $pdo->prepare("
            SELECT e.id, e.nombre,
                (SUM(COALESCE(mg.semana_likes,0)) + SUM(COALESCE(vu.semana_vistas,0))) AS puntuacion
            FROM etiquetas e
            JOIN recetas_etiquetas re ON re.etiqueta_id = e.id
            JOIN recetas r ON r.id = re.receta_id
            LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_likes FROM me_gusta WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) mg ON mg.receta_id = r.id
            LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_vistas FROM vistas_unicas WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) vu ON vu.receta_id = r.id
            WHERE r.estado = 1
            GROUP BY e.id
            ORDER BY puntuacion DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $total, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $sql = "
        SELECT et.id, et.nombre, SUM(peso) as score
        FROM (
            SELECT receta_id, 1 as peso FROM historial WHERE usuario_id = :uid
            UNION ALL
            SELECT receta_id, 3 as peso FROM me_gusta WHERE usuario_id = :uid
            UNION ALL
            SELECT receta_id, 5 as peso FROM favoritos WHERE usuario_id = :uid
        ) AS interacciones
        JOIN recetas_etiquetas re ON re.receta_id = interacciones.receta_id
        JOIN etiquetas et ON et.id = re.etiqueta_id
        GROUP BY et.id
        ORDER BY score DESC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $total, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) < $total) {
        $faltantes = $total - count($result);
        $idsExistentes = array_column($result, 'id');
        $placeholders = $idsExistentes ? 'AND e.id NOT IN (' . implode(',', array_fill(0, count($idsExistentes), '?')) . ')' : '';
        $sqlGlobal = "
            SELECT e.id, e.nombre,
                (SUM(COALESCE(mg.semana_likes,0)) + SUM(COALESCE(vu.semana_vistas,0))) AS puntuacion
            FROM etiquetas e
            JOIN recetas_etiquetas re ON re.etiqueta_id = e.id
            JOIN recetas r ON r.id = re.receta_id
            LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_likes FROM me_gusta WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) mg ON mg.receta_id = r.id
            LEFT JOIN (SELECT receta_id, COUNT(*) AS semana_vistas FROM vistas_unicas WHERE fecha >= CURDATE() - INTERVAL 7 DAY GROUP BY receta_id) vu ON vu.receta_id = r.id
            WHERE r.estado = 1 $placeholders
            GROUP BY e.id
            ORDER BY puntuacion DESC
            LIMIT $faltantes
        ";
        $stmtGlobal = $pdo->prepare($sqlGlobal);
        if ($idsExistentes) {
            $stmtGlobal->execute($idsExistentes);
        } else {
            $stmtGlobal->execute();
        }
        $globalTags = $stmtGlobal->fetchAll(PDO::FETCH_ASSOC);
        $result = array_merge($result, $globalTags);
    }
    return $result;
}

$tags = obtenerEtiquetasPreferidas($pdo, $usuarioId, $offset + $limit);
$tags = array_slice($tags, $offset, $limit);

$secciones = [];
foreach ($tags as $tag) {
    $stmtRec = $pdo->prepare("
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
    $stmtRec->execute([$tag['id']]);
    $recetas = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
    if (empty($recetas)) {
        continue;
    }
    foreach ($recetas as &$rec) {
        $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
    }
    $secciones[] = [
        'nombre' => $tag['nombre'],
        'recetas' => $recetas
    ];
}
echo json_encode(['ok' => true, 'secciones' => $secciones]);