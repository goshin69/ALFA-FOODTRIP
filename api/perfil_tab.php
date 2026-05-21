<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'publicaciones';
$order = isset($_GET['order']) ? $_GET['order'] : 'recientes';
$baseUrl = '/ALFA/public/';

if (!$user_id) exit;

$es_dueno = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $user_id;
if (!$es_dueno && $tab !== 'publicaciones') $tab = 'publicaciones';

$stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user_row = $stmt->fetch(PDO::FETCH_ASSOC);
$user_nombre = $user_row ? $user_row['nombre'] : 'Usuario';

switch ($tab) {
    case 'favoritos':
        $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen, f.fecha as fecha_fav
                FROM favoritos f
                JOIN recetas r ON f.receta_id = r.id AND r.estado = 1
                LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                WHERE f.usuario_id = ?
                ORDER BY f.fecha DESC";
        break;
    case 'me_gusta':
        $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen, mg.fecha as fecha_mg
                FROM me_gusta mg
                JOIN recetas r ON mg.receta_id = r.id AND r.estado = 1
                LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                WHERE mg.usuario_id = ?
                ORDER BY mg.fecha DESC";
        break;
    case 'historial':
        $sql = "SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen, h.fecha as fecha_hist
                FROM historial h
                JOIN recetas r ON h.receta_id = r.id AND r.estado = 1
                LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                WHERE h.usuario_id = ?
                ORDER BY h.fecha DESC";
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
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $weeks = floor($diff->d / 7);
    $days_remaining = $diff->d - $weeks * 7;
    
    $string = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days_remaining,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    );
    
    $labels = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo'
    );
    
    $result = array();
    foreach ($string as $k => $v) {
        if ($v > 0) {
            $result[$k] = $v . ' ' . $labels[$k] . ($v > 1 ? ($k == 'y' ? 's' : 's') : '');
        }
    }
    
    if (!$full) {
        $result = array_slice($result, 0, 1);
    }
    
    return $result ? implode(', ', $result) . ' atrás' : 'justo ahora';
}

if (empty($items)) {
    echo '<p>No hay contenido para mostrar.</p>';
} else {
    foreach ($items as $item) {
        echo '<div class="publication-card" onclick="location.href=\'receta.php?id=' . $item['id'] . '\'">';
        echo '<div class="card-image"><img src="' . $baseUrl . htmlspecialchars($item['imagen'] ?? 'assets/img/default_receta.jpg') . '" alt="' . htmlspecialchars($item['titulo']) . '"></div>';
        echo '<div class="card-content">';
        echo '<h3 class="card-title">' . htmlspecialchars($item['titulo']) . '</h3>';
        echo '<p class="card-author">Por: ' . htmlspecialchars($user_nombre) . '</p>';
        echo '<span class="card-time">' . time_elapsed_string($item['fecha_publicacion']) . '</span>';
        echo '</div></div>';
    }
}