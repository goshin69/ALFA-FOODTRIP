<?php
$baseUrl = '/ALFA/';
include '../includes/header.php';
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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM recetas WHERE usuario_id = ? AND estado = 1");
$stmt->execute([$usuario_id]);
$recetas_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
$stmt->execute([$usuario_id]);
$seguidores_count = $stmt->fetchColumn();

$siguiendo = false;
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] != $usuario_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $usuario_id]);
    $siguiendo = (bool)$stmt->fetchColumn();
}

$stmt = $pdo->prepare("SELECT r.id, r.titulo, r.fecha_publicacion, i.ruta as imagen
                       FROM recetas r
                       LEFT JOIN (SELECT receta_id, MIN(ruta) as ruta FROM imagenes GROUP BY receta_id) i ON r.id = i.receta_id
                       WHERE r.usuario_id = ? AND r.estado = 1
                       ORDER BY r.fecha_publicacion DESC");
$stmt->execute([$usuario_id]);
$recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array('y' => 'año', 'm' => 'mes', 'w' => 'semana', 'd' => 'día', 'h' => 'hora', 'i' => 'minuto', 's' => 'segundo');
    foreach ($string as $k => &$v) if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); else unset($string[$k]);
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' atrás' : 'justo ahora';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($usuario['nombre']) ?> - Perfil | Koalicius</title>
    <link rel="stylesheet" href="assets/css/global.css?v=5.3">
    <link rel="stylesheet" href="assets/css/perfil.css?v=5.3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <main>
        <div class="header-banner"></div>
        <button class="exit-button" onclick="history.back()"><i class="fas fa-door-open"></i> Salir</button>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-photo">
                    <img src="<?= $baseUrl . (!empty($usuario['imagen_perfil']) ? htmlspecialchars($usuario['imagen_perfil']) : 'imageness/Logo Sesion.png') ?>" alt="Foto de perfil" id="profile-image">
                </div>
                <div class="profile-info">
                    <div class="user-header">
                        <div class="user-name-section">
                            <h1 class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></h1>
                            <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuario_id): ?>
                                <button class="btn-edit"><i class="fas fa-edit"></i> Editar</button>
                                <button class="btn-upload" onclick="location.href='crear_receta.php'"><i class="fas fa-upload"></i> Subir Recetas</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="social-stats">
                        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] != $usuario_id): ?>
                            <button id="btnSeguir" data-user-id="<?= $usuario_id ?>" class="<?= $siguiendo ? 'siguiendo' : '' ?>"><?= $siguiendo ? 'Siguiendo' : 'Seguir' ?></button>
                        <?php endif; ?>
                        <div class="stats-section">
                            <div class="stat">
                                <span class="stat-number" id="recipes-count"><?= $recetas_count ?></span>
                                <span class="stat-label">Recetas</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number" id="followers-count"><?= $seguidores_count ?></span>
                                <span class="stat-label">Seguidores</span>
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
                <h2 class="section-title">Publicaciones</h2>
                <div class="publications-grid">
                    <?php if (empty($recetas)): ?>
                        <p>No hay recetas publicadas aún.</p>
                    <?php else: foreach ($recetas as $receta): ?>
                        <div class="publication-card" onclick="location.href='receta.php?id=<?= $receta['id'] ?>'">
                            <div class="card-image">
                                <img src="<?= $baseUrl . htmlspecialchars($receta['imagen'] ?? 'imageness/default_receta.jpg') ?>" alt="<?= htmlspecialchars($receta['titulo']) ?>">
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?= htmlspecialchars($receta['titulo']) ?></h3>
                                <p class="card-author">Por: <?= htmlspecialchars($usuario['nombre']) ?></p>
                                <span class="card-time"><?= time_elapsed_string($receta['fecha_publicacion']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuario_id): ?>
    <div class="overlay"></div>
    <div class="edit-panel">
        <h3>Editar perfil</h3>
        <form id="edit-form">
            <div class="edit-photo-section">
                <img src="<?= $baseUrl . (!empty($usuario['imagen_perfil']) ? htmlspecialchars($usuario['imagen_perfil']) : 'imageness/Logo Sesion.png') ?>" alt="Vista previa" class="edit-photo-preview" id="photo-preview">
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

    <script src="assets/js/global.js?v=5.3"></script>
    <script src="assets/js/perfil.js?v=5.3"></script>
</body>
</html>