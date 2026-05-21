<?php
ob_start();
header('Content-Type: application/json');

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/ALFA/includes/database.php';

    if (!isset($pdo)) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $action = $_GET['action'] ?? null;

    if ($action === 'etiquetas') {
        $stmt = $pdo->query("SELECT id, nombre FROM etiquetas ORDER BY nombre");
        $etiquetasLista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'etiquetas' => $etiquetasLista]);
        exit;
    }
    if ($action === 'populares') {
        $stmt = $pdo->query("
            SELECT e.id, e.nombre, COUNT(re.receta_id) as total
            FROM etiquetas e
            JOIN recetas_etiquetas re ON e.id = re.etiqueta_id
            GROUP BY e.id
            ORDER BY total DESC
            LIMIT 6
        ");
        $populares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'populares' => $populares]);
        exit;
    }

    $q = trim($_GET['q'] ?? '');
    $tiempo_max = isset($_GET['tiempo_max']) && $_GET['tiempo_max'] !== '' ? (int)$_GET['tiempo_max'] : null;
    $order = $_GET['order'] ?? 'popular';
    $etiquetas = isset($_GET['etiquetas']) ? (array)$_GET['etiquetas'] : [];
    $excluir_etiquetas = isset($_GET['excluir_etiquetas']) ? (array)$_GET['excluir_etiquetas'] : [];
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $limite = 12;
    $offset = ($pagina - 1) * $limite;

    $etiquetas = array_filter($etiquetas, 'is_numeric');
    $excluir_etiquetas = array_filter($excluir_etiquetas, 'is_numeric');

    $sqlBase = "FROM recetas r
                JOIN usuarios u ON r.usuario_id = u.id";
    $conditions = [];
    $params = [];

    if (!empty($q)) {
        $conditions[] = "(r.titulo LIKE :q OR r.descripcion LIKE :q OR r.ingredientes LIKE :q)";
        $params[':q'] = "%$q%";
    }

    if ($tiempo_max !== null) {
        if ($tiempo_max == 61) {
            $conditions[] = "r.tiempo_preparacion > 60";
        } else {
            $conditions[] = "r.tiempo_preparacion <= :tiempo_max";
            $params[':tiempo_max'] = $tiempo_max;
        }
    }

    $joinCounter = 0;
    foreach ($etiquetas as $etId) {
        $alias = "re$joinCounter";
        $sqlBase .= " JOIN recetas_etiquetas $alias ON r.id = $alias.receta_id AND $alias.etiqueta_id = :et_$joinCounter";
        $params[":et_$joinCounter"] = $etId;
        $joinCounter++;
    }

    if (!empty($excluir_etiquetas)) {
        $placeholders = [];
        foreach ($excluir_etiquetas as $idx => $etId) {
            $placeholders[] = ":excl_$idx";
            $params[":excl_$idx"] = $etId;
        }
        $conditions[] = "r.id NOT IN (
            SELECT receta_id FROM recetas_etiquetas 
            WHERE etiqueta_id IN (" . implode(',', $placeholders) . ")
        )";
    }

    $conditions[] = "r.estado = 1";

    $whereSql = "";
    if (!empty($conditions)) {
        $whereSql = " WHERE " . implode(" AND ", $conditions);
    }

    $countSql = "SELECT COUNT(DISTINCT r.id) $sqlBase $whereSql";
    $stmtCount = $pdo->prepare($countSql);
    foreach ($params as $key => $val) {
        $stmtCount->bindValue($key, $val);
    }
    $stmtCount->execute();
    $total = (int)$stmtCount->fetchColumn();

    $sql = "SELECT DISTINCT r.id, r.titulo, r.descripcion AS descripcion_corta,
                   (SELECT i.ruta FROM imagenes i WHERE i.receta_id = r.id ORDER BY i.orden ASC LIMIT 1) AS imagen,
                   r.tiempo_preparacion, r.vistas,
                   u.nombre AS autor_nombre,
                   u.id AS autor_id
            $sqlBase
            $whereSql";

    switch ($order) {
        case 'popular':
            $sql .= " ORDER BY r.vistas DESC";
            break;
        case 'nombre':
            $sql .= " ORDER BY r.titulo ASC";
            break;
        case 'reciente':
        default:
            $sql .= " ORDER BY r.fecha_publicacion DESC";
            break;
    }

    $sql .= " LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recetas as &$rec) {
        if (!empty($rec['imagen']) && strpos($rec['imagen'], 'http') !== 0) {
            $rec['imagen'] = '/ALFA/public/' . ltrim($rec['imagen'], '/');
        } elseif (empty($rec['imagen'])) {
            $rec['imagen'] = '/ALFA/public/assets/img/default-receta.jpg';
        }
    }

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'pagina' => $pagina,
        'recetas' => $recetas
    ]);

} catch (Exception $e) {
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/errores_api.log';
    $mensaje = date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    file_put_contents($logFile, $mensaje, FILE_APPEND);
    echo json_encode([
        'ok' => false,
        'error' => 'Error interno del servidor'
    ]);
} finally {
    ob_end_flush();
}