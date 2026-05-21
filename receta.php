<?php
session_start();
require_once '../includes/database.php';
$baseUrl = '/ALFA/public/';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Receta no válida');
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT r.id, r.titulo, r.descripcion, r.preparacion, r.ingredientes,
                              r.tiempo_preparacion, r.dificultad, r.fecha_publicacion, r.estado,
                              u.nombre as autor, u.id as autor_id, u.imagen_perfil as autor_foto
                       FROM recetas r
                       JOIN usuarios u ON r.usuario_id = u.id
                       WHERE r.id = ? AND r.estado = 1");
$stmt->execute([$id]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    die('Receta no encontrada');
}

if (isset($_SESSION['usuario_id'])) {
    $usuario_vista = $_SESSION['usuario_id'];
    $ip_hash = 'registrado';
} else {
    $usuario_vista = null;
    $ip_hash = md5($_SERVER['REMOTE_ADDR']);
}
$stmt = $pdo->prepare("INSERT IGNORE INTO vistas_unicas (receta_id, usuario_id, ip_hash) VALUES (?, ?, ?)");
$stmt->execute([$id, $usuario_vista, $ip_hash]);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM vistas_unicas WHERE receta_id = ?");
$stmt->execute([$id]);
$total_vistas = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("UPDATE recetas SET vistas = ? WHERE id = ?");
$stmt->execute([$total_vistas, $id]);

$stmt = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden ASC");
$stmt->execute([$id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT e.nombre, e.id FROM etiquetas e
                       JOIN recetas_etiquetas re ON e.id = re.etiqueta_id
                       WHERE re.receta_id = ?");
$stmt->execute([$id]);
$etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$me_gusta_activo = false;
$favorito_activo = false;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT 1 FROM me_gusta WHERE usuario_id = ? AND receta_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $id]);
    $me_gusta_activo = (bool)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND receta_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $id]);
    $favorito_activo = (bool)$stmt->fetchColumn();
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM me_gusta WHERE receta_id = ?");
$stmt->execute([$id]);
$total_me_gusta = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE receta_id = ?");
$stmt->execute([$id]);
$total_favoritos = $stmt->fetchColumn();

$usuario_rol = $_SESSION['usuario_rol'] ?? 'usuario';
$es_admin_o_mod = in_array($usuario_rol, ['admin', 'moderador']);
$es_autor = (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $receta['autor_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($receta['titulo']) ?> - Koalicius</title>
    <link rel="icon" type="image/x-icon" href="assets/img/koali.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css?v=5.10">
    <link rel="stylesheet" href="assets/css/receta.css?v=5.13">
</head>
<body data-usuario-id="<?= $_SESSION['usuario_id'] ?? '' ?>" data-usuario-rol="<?= $_SESSION['usuario_rol'] ?? 'usuario' ?>">
    <?php include '../includes/header.php'; ?>

    <main class="receta-detalle" data-receta-id="<?= $id ?>" data-autor-id="<?= $receta['autor_id'] ?>">
        <div class="receta-header">
            <h1 data-i18n="titulo"><?= htmlspecialchars($receta['titulo']) ?></h1>
            <div class="autor-info">
                <img src="<?= $baseUrl . (!empty($receta['autor_foto']) ? htmlspecialchars($receta['autor_foto']) : 'assets/img/Logo Sesion.png') ?>" alt="Avatar">
                <a href="perfil.php?id=<?= $receta['autor_id'] ?>"><?= htmlspecialchars($receta['autor']) ?></a>
                <span class="fecha" data-i18n="fecha">Publicado el <?= date('d/m/Y', strtotime($receta['fecha_publicacion'])) ?></span>
            </div>
        </div>

        <div class="receta-meta">
            <span><i class="fa-regular fa-clock"></i> <span data-i18n="tiempo"><?= $receta['tiempo_preparacion'] ?> min</span></span>
            <span><i class="fa-regular fa-solid fa-chart-simple"></i> <span data-i18n="dificultad">Dificultad: <?= ucfirst($receta['dificultad']) ?></span></span>
            <span><i class="fa-regular fa-eye"></i> <span id="vistas-count"><?= $total_vistas ?></span> <span data-i18n="vistas">vistas</span></span>
        </div>

        <div class="receta-descripcion">
            <h2 data-i18n="desc_titulo">Descripción</h2>
            <p><?= nl2br(htmlspecialchars($receta['descripcion'])) ?></p>
        </div>

        <div class="receta-ingredientes">
            <h2 data-i18n="ing_titulo">Ingredientes</h2>
            <pre><?= htmlspecialchars($receta['ingredientes']) ?></pre>
        </div>

        <div class="receta-preparacion">
            <h2 data-i18n="prep_titulo">Preparación</h2>
            <?= nl2br(htmlspecialchars($receta['preparacion'])) ?>
        </div>

        <?php if (!empty($imagenes)): ?>
        <div class="receta-imagenes">
            <h2 data-i18n="galeria">Galería</h2>
            <div class="galeria">
                <?php foreach ($imagenes as $img): ?>
                    <?php if (preg_match('/\.(mp4|avi|mov|mkv|webm)$/i', $img)): ?>
                        <video controls src="<?= $baseUrl . htmlspecialchars($img) ?>" class="galeria-item" loading="lazy"></video>
                    <?php else: ?>
                        <img src="<?= $baseUrl . htmlspecialchars($img) ?>" alt="Imagen de receta" class="galeria-item" loading="lazy">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="receta-etiquetas">
            <h2 data-i18n="etiquetas">Etiquetas</h2>
            <div class="etiquetas-lista">
                <?php foreach ($etiquetas as $et): ?>
                    <a href="buscar.php?etiquetas[]=<?= $et['id'] ?>" class="etiqueta"><?= htmlspecialchars($et['nombre']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="receta-acciones">
            <button class="btn-me-gusta <?= $me_gusta_activo ? 'activo' : '' ?>" data-receta-id="<?= $id ?>">
                <i class="fa-heart <?= $me_gusta_activo ? 'fa-solid' : 'fa-regular' ?>"></i>
                <span class="count"><?= $total_me_gusta ?></span>
            </button>
            <button class="btn-favorito <?= $favorito_activo ? 'activo' : '' ?>" data-receta-id="<?= $id ?>">
                <i class="fa-bookmark <?= $favorito_activo ? 'fa-solid' : 'fa-regular' ?>"></i>
                <span class="count"><?= $total_favoritos ?></span>
            </button>
        </div>

        <div class="admin-actions">
            <button id="btn-acciones" class="btn-acciones">
                <i class="fa-solid fa-ellipsis-vertical"></i> <span data-i18n="mas_acciones">Más acciones</span>
            </button>
            <div id="acciones-dropdown" class="acciones-dropdown hidden">
                <?php if (isset($_SESSION['usuario_id']) && ($es_autor || $es_admin_o_mod)): ?>
                    <button id="btn-eliminar-receta" class="accion-btn">
                        <i class="fa-solid fa-trash"></i> <span data-i18n="eliminar">Eliminar receta</span>
                    </button>
                <?php endif; ?>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <button id="btn-demandar" class="accion-btn accion-btn-peligro">
                        <i class="fa-solid fa-flag"></i> <span data-i18n="denunciar">Denunciar receta</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="comentarios-seccion">
            <h2 data-i18n="comentarios">Comentarios (<span id="comentarios-count">0</span>)</h2>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <form id="form-comentario" class="form-comentario">
                    <textarea id="comentario-texto" rows="3" placeholder="Escribe un comentario..." data-i18n-placeholder="comentario_placeholder"></textarea>
                    <input type="hidden" id="comentario-parent-id" value="0">
                    <button type="submit" class="btn-enviar-comentario" data-i18n="enviar">Enviar comentario</button>
                </form>
            <?php else: ?>
                <p class="login-para-comentar" data-i18n="login_comentar"><a href="login.php">Inicia sesión</a> para comentar.</p>
            <?php endif; ?>
            <div id="lista-comentarios" class="lista-comentarios"></div>
        </div>
    </main>

    <div id="lightbox-modal" class="lightbox-modal hidden">
        <span class="lightbox-close">&times;</span>
        <span class="lightbox-prev">&lt;</span>
        <span class="lightbox-next">&gt;</span>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="">
            <video id="lightbox-video" controls style="display:none;"></video>
        </div>
    </div>

    <div id="modal-denuncia" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 data-i18n="denunciar_titulo">Denunciar receta</h3>
            <form id="form-denuncia">
                <div class="campo">
                    <label data-i18n="motivo">Motivo:</label>
                    <select id="motivo-denuncia" required>
                        <option value="acoso" data-i18n="m_acoso">Acoso</option>
                        <option value="spam" data-i18n="m_spam">Spam</option>
                        <option value="inapropiado" data-i18n="m_inapropiado">Contenido inapropiado</option>
                        <option value="derechos_autor" data-i18n="m_derechos">Infracción de derechos de autor</option>
                        <option value="violencia" data-i18n="m_violencia">Violencia o lenguaje ofensivo</option>
                        <option value="suplantacion" data-i18n="m_suplantacion">Suplantación de identidad</option>
                        <option value="otro" data-i18n="m_otro">Otro</option>
                    </select>
                </div>
                <div class="campo">
                    <label data-i18n="detalle">Detalle (opcional):</label>
                    <textarea id="detalle-denuncia" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" data-i18n="cancelar">Cancelar</button>
                    <button type="submit" class="btn-confirmar" data-i18n="enviar_denuncia">Enviar denuncia</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-denuncia-comentario" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 data-i18n="denunciar_comentario_titulo">Denunciar comentario</h3>
            <form id="form-denuncia-comentario">
                <div class="campo">
                    <label data-i18n="motivo_denuncia_comentario">Motivo:</label>
                    <select id="motivo-denuncia-comentario" required>
                        <option value="acoso" data-i18n="acoso">Acoso</option>
                        <option value="spam" data-i18n="spam">Spam</option>
                        <option value="inapropiado" data-i18n="inapropiado">Contenido inapropiado</option>
                        <option value="odio" data-i18n="odio">Discurso de odio</option>
                        <option value="falso" data-i18n="falso">Información falsa</option>
                        <option value="otro" data-i18n="otro">Otro</option>
                    </select>
                </div>
                <div class="campo">
                    <label data-i18n="detalle">Detalle (opcional):</label>
                    <textarea id="detalle-denuncia-comentario" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" data-i18n="cancelar">Cancelar</button>
                    <button type="submit" class="btn-confirmar" data-i18n="enviar_denuncia_comentario">Enviar denuncia</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-eliminar" class="modal-overlay hidden">
        <div class="modal-content">
            <h3 data-i18n="confirmar_eliminar">Confirmar eliminación</h3>
            <p data-i18n="confirmar_texto">¿Estás seguro de que deseas eliminar esta receta? Esta acción no se puede deshacer.</p>
            <div class="modal-actions">
                <button type="button" id="cancelar-eliminar" class="btn-cancelar" data-i18n="cancelar">Cancelar</button>
                <button type="button" id="confirmar-eliminar" class="btn-confirmar" data-i18n="eliminar">Eliminar</button>
            </div>
        </div>
    </div>

    <div id="notificacion" class="notificacion hidden"></div>

    <script src="assets/js/global.js?v=5.10"></script>
    <script src="assets/js/receta.js?v=5.13"></script>
</body>
</html>