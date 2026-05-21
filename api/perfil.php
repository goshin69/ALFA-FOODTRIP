<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (in_array($action, ['follow', 'unfollow', 'get_followers', 'get_following']) && !isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$usuario_logueado = $_SESSION['usuario_id'] ?? 0;

if ($action === 'get_tab') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    $tab = $_GET['tab'] ?? 'publicaciones';
    $order = $_GET['order'] ?? 'recientes';
    $baseUrl = '/ALFA/public/';

    if (!$user_id) {
        echo json_encode(['ok' => false, 'error' => 'Falta user_id']);
        exit;
    }

    $es_dueno = ($usuario_logueado == $user_id);
    if (!$es_dueno && $tab !== 'publicaciones') {
        $tab = 'publicaciones';
    }

    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_nombre = $stmt->fetchColumn() ?: 'Usuario';

    switch ($tab) {
        case 'favoritos':
            $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen
                    FROM favoritos f
                    JOIN recetas r ON f.receta_id = r.id AND r.estado = 1
                    LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                    WHERE f.usuario_id = ?
                    ORDER BY f.fecha DESC";
            $params = [$user_id];
            break;
        case 'me_gusta':
            $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen
                    FROM me_gusta mg
                    JOIN recetas r ON mg.receta_id = r.id AND r.estado = 1
                    LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                    WHERE mg.usuario_id = ?
                    ORDER BY mg.fecha DESC";
            $params = [$user_id];
            break;
        case 'historial':
            $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen
                    FROM historial h
                    JOIN recetas r ON h.receta_id = r.id AND r.estado = 1
                    LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                    WHERE h.usuario_id = ?
                    ORDER BY h.fecha DESC";
            $params = [$user_id];
            break;
        default:
            $order_clause = "ORDER BY r.fecha_publicacion DESC";
            if ($order === 'antiguas') $order_clause = "ORDER BY r.fecha_publicacion ASC";
            elseif ($order === 'vistas') $order_clause = "ORDER BY r.vistas DESC, r.fecha_publicacion DESC";
            $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen, r.vistas
                    FROM recetas r
                    LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                    WHERE r.usuario_id = ? AND r.estado = 1
                    $order_clause";
            $params = [$user_id];
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function time_elapsed_string($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $weeks = floor($diff->d / 7);
        $days = $diff->d - $weeks * 7;
        $parts = [];
        if ($diff->y) $parts[] = $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
        elseif ($diff->m) $parts[] = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
        elseif ($weeks) $parts[] = $weeks . ' semana' . ($weeks > 1 ? 's' : '');
        elseif ($days) $parts[] = $days . ' día' . ($days > 1 ? 's' : '');
        elseif ($diff->h) $parts[] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        elseif ($diff->i) $parts[] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        else $parts[] = $diff->s . ' segundo' . ($diff->s != 1 ? 's' : '');
        return implode(', ', $parts) . ' atrás';
    }

    if (empty($items)) {
        echo '<p>No hay contenido para mostrar.</p>';
    } else {
        foreach ($items as $item) {
            $img = !empty($item['imagen']) ? $baseUrl . htmlspecialchars($item['imagen']) : $baseUrl . 'assets/img/default_receta.jpg';
            echo '<div class="publication-card" onclick="location.href=\'receta.php?id=' . $item['id'] . '\'">';
            echo '<div class="card-image"><img src="' . $img . '" alt="' . htmlspecialchars($item['titulo']) . '"></div>';
            echo '<div class="card-content">';
            echo '<h3 class="card-title">' . htmlspecialchars($item['titulo']) . '</h3>';
            echo '<p class="card-author">Por: ' . htmlspecialchars($user_nombre) . '</p>';
            echo '<span class="card-time">' . time_elapsed_string($item['fecha_publicacion']) . '</span>';
            echo '</div></div>';
        }
    }
    exit;
}

if ($action === 'follow' || $action === 'unfollow') {
    $seguido_id = (int)($_POST['seguido_id'] ?? 0);
    if (!$seguido_id || $seguido_id == $usuario_logueado) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }

    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
        $stmt->execute([$usuario_logueado, $seguido_id]);
        $siguiendo = true;
    } else {
        $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmt->execute([$usuario_logueado, $seguido_id]);
        $siguiendo = false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
    $stmt->execute([$seguido_id]);
    $seguidores = (int)$stmt->fetchColumn();

    echo json_encode(['ok' => true, 'siguiendo' => $siguiendo, 'seguidores' => $seguidores]);
    exit;
}

if ($action === 'get_followers') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    if (!$user_id) {
        echo json_encode(['ok' => false, 'error' => 'Falta user_id']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.imagen_perfil 
        FROM usuarios u
        JOIN seguidores s ON u.id = s.seguidor_id
        WHERE s.seguido_id = ?
        ORDER BY s.fecha DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
    exit;
}

if ($action === 'get_following') {
    $user_id = (int)($_GET['user_id'] ?? 0);
    if (!$user_id) {
        echo json_encode(['ok' => false, 'error' => 'Falta user_id']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.imagen_perfil 
        FROM usuarios u
        JOIN seguidores s ON u.id = s.seguido_id
        WHERE s.seguidor_id = ?
        ORDER BY s.fecha DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);