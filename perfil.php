<?php
$baseUrl = '/ALFA/public/';

ob_start();
include '../includes/header.php';
$header_html = ob_get_clean();

require_once '../includes/database.php';

$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['usuario_id'] ?? 0);
if ($usuario_id == 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, biografia, fecha_nacimiento, imagen_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$usuario) {
    http_response_code(404);
    echo "Usuario no encontrado";
    exit;
}

$es_dueno = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuario_id;
$esta_logueado = isset($_SESSION['usuario_id']);

$tab = $_GET['tab'] ?? 'publicaciones';
$allowed_tabs = ['publicaciones', 'favoritos', 'me_gusta', 'historial'];
if (!in_array($tab, $allowed_tabs)) $tab = 'publicaciones';
if (!$es_dueno && $tab !== 'publicaciones') {
    $tab = 'publicaciones';
}

$order = $_GET['order'] ?? 'recientes';
$allowed_orders = ['recientes', 'antiguas', 'vistas'];
if (!in_array($order, $allowed_orders)) $order = 'recientes';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM recetas WHERE usuario_id = ? AND estado = 1");
$stmt->execute([$usuario_id]);
$recetas_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
$stmt->execute([$usuario_id]);
$seguidores_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?");
$stmt->execute([$usuario_id]);
$siguiendo_count = $stmt->fetchColumn();

$siguiendo = false;
if ($esta_logueado && $_SESSION['usuario_id'] != $usuario_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $usuario_id]);
    $siguiendo = (bool)$stmt->fetchColumn();
}

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($usuario['nombre']) ?> - Perfil | Koalicius</title>
    <link rel="icon" type="image/x-icon" href="assets/img/koali.ico">
    <link rel="stylesheet" href="assets/css/global.css?v=5.3">
    <link rel="stylesheet" href="assets/css/header-style.css?v=5.8">
    <link rel="stylesheet" href="assets/css/perfil.css?v=5.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?= $header_html ?>
    <div class="header-banner"></div>
    <main>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-photo">
                    <img src="<?= $baseUrl . (!empty($usuario['imagen_perfil']) ? htmlspecialchars($usuario['imagen_perfil']) : 'assets/img/Logo Sesion.png') ?>" alt="Foto de perfil" id="profile-image">
                </div>
                <div class="profile-info">
                    <div class="user-header">
                        <div class="user-name-section">
                            <h1 class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></h1>
                            <?php if ($es_dueno): ?>
                                <button class="btn-edit"><i class="fas fa-edit"></i> Editar</button>
                                <button class="btn-upload" onclick="location.href='crear_receta.php'"><i class="fas fa-upload"></i> Subir Recetas</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="social-stats">
                        <?php if ($esta_logueado && $_SESSION['usuario_id'] != $usuario_id): ?>
                            <button id="btnSeguir" data-user-id="<?= $usuario_id ?>" class="<?= $siguiendo ? 'siguiendo' : '' ?>"><?= $siguiendo ? 'Siguiendo' : 'Seguir' ?></button>
                        <?php endif; ?>
                        <div class="stats-section">
                            <div class="stat">
                                <span class="stat-number"><?= $recetas_count ?></span>
                                <span class="stat-label">Recetas</span>
                            </div>
                            <div class="stat clickable" data-modal="seguidores">
                                <span class="stat-number"><?= $seguidores_count ?></span>
                                <span class="stat-label">Seguidores</span>
                            </div>
                            <div class="stat clickable" data-modal="siguiendo">
                                <span class="stat-number"><?= $siguiendo_count ?></span>
                                <span class="stat-label">Siguiendo</span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-bio">
                        <p class="bio-text"><?= nl2br(htmlspecialchars($usuario['biografia'] ?? '')) ?></p>
                    </div>
                    <div class="profile-birthday">
                        <i class="fas fa-birthday-cake"></i> <span class="birthday-date"><?= $usuario['fecha_nacimiento'] ? date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) : 'No especificada' ?></span>
                    </div>
                </div>
            </div>

            <div class="publications-section">
                <div class="section-header">
                    <div class="tab-bar">
                        <button class="tab-btn active" data-tab="publicaciones">Publicaciones</button>
                        <?php if ($es_dueno): ?>
                        <button class="tab-btn" data-tab="favoritos">Favoritos</button>
                        <button class="tab-btn" data-tab="me_gusta">Me gusta</button>
                        <button class="tab-btn" data-tab="historial">Historial</button>
                        <?php endif; ?>
                    </div>
                    <form class="order-form" id="orderForm">
                        <select name="order" id="orderSelect">
                            <option value="recientes" <?= $order == 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                            <option value="antiguas" <?= $order == 'antiguas' ? 'selected' : '' ?>>Más antiguas</option>
                            <option value="vistas" <?= $order == 'vistas' ? 'selected' : '' ?>>Más vistas</option>
                        </select>
                    </form>
                </div>

                <div class="publications-grid" id="publicationsGrid">
                </div>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="modalOverlay" style="display:none;"></div>
    <div class="modal" id="modalLista" style="display:none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 id="modalTitle"></h3>
            <div id="modalBody"></div>
        </div>
    </div>

    <?php if ($es_dueno): ?>
    <div class="overlay"></div>
    <div class="edit-panel">
        <h3>Editar perfil</h3>
        <form id="edit-form">
            <div class="edit-photo-section">
                <img src="<?= $baseUrl . (!empty($usuario['imagen_perfil']) ? htmlspecialchars($usuario['imagen_perfil']) : 'assets/img/Logo Sesion.png') ?>" alt="Vista previa" class="edit-photo-preview" id="photo-preview">
                <label for="edit-photo-input" class="edit-photo-btn"><i class="fas fa-camera"></i> Cambiar foto</label>
                <input type="file" id="edit-photo-input" accept="image/*">
            </div>
            <label for="edit-name">Nombre</label>
            <input type="text" id="edit-name" value="<?= htmlspecialchars($usuario['nombre']) ?>">
            <label for="edit-bio">Biografía</label>
            <textarea id="edit-bio"><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
            <label for="edit-birthday">Fecha de cumpleaños</label>
            <input type="date" id="edit-birthday" value="<?= $usuario['fecha_nacimiento'] ?? '' ?>">
            <div class="btn-group">
                <button type="button" class="btn-save">Guardar</button>
                <button type="button" class="btn-cancel">Cancelar</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <script>
        window.perfilData = {
            userId: <?= $usuario_id ?>,
            baseUrl: '<?= $baseUrl ?>',
            esDueno: <?= $es_dueno ? 'true' : 'false' ?>,
            loggedIn: <?= $esta_logueado ? 'true' : 'false' ?>
        };
    </script>
    <script src="assets/js/global.js?v=5.3"></script>
    <script src="assets/js/perfil.js?v=5.4"></script>
    <div id="notif-overlay" class="notif-overlay hidden"></div>
</body>
</html>