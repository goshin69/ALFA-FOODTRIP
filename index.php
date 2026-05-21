<?php
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/ALFA/';
require_once $rootPath . 'includes/database.php';
session_start();

$baseUrl = '/ALFA/public/';
$logueado = isset($_SESSION['usuario_id']);
$usuarioId = $logueado ? (int)$_SESSION['usuario_id'] : 0;

$seccionesIniciales = [];

$stmtTop = $pdo->prepare("
    SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, r.tiempo_preparacion,
           (SELECT MIN(i.ruta) FROM imagenes i WHERE i.receta_id = r.id) as imagen,
           u.nombre as autor_nombre, u.id as autor_id,
           (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.estado = 1 AND r.fecha_publicacion >= CURDATE() - INTERVAL 7 DAY
    ORDER BY total_vistas DESC
    LIMIT 12
");
$stmtTop->execute();
$recetasTop = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
foreach ($recetasTop as &$rec) {
    $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
}
if (!empty($recetasTop)) {
    $seccionesIniciales[] = ['nombre' => 'Lo mas visto', 'recetas' => $recetasTop];
}

if ($logueado) {
    $stmtFollow = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.tiempo_preparacion,
               (SELECT MIN(i.ruta) FROM imagenes i WHERE i.receta_id = r.id) as imagen,
               u.nombre as autor_nombre, u.id as autor_id,
               (SELECT COUNT(*) FROM vistas_unicas vu WHERE vu.receta_id = r.id) as total_vistas
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.estado = 1 AND r.usuario_id IN (SELECT seguido_id FROM seguidores WHERE seguidor_id = ?)
        ORDER BY r.fecha_publicacion DESC
        LIMIT 12
    ");
    $stmtFollow->execute([$usuarioId]);
    $recetasFollow = $stmtFollow->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recetasFollow as &$rec) {
        $rec['imagen'] = !empty($rec['imagen']) ? $rec['imagen'] : 'imageness/default_receta.jpg';
    }
    if (!empty($recetasFollow)) {
        $seccionesIniciales[] = ['nombre' => 'Recetas de los que sigues', 'recetas' => $recetasFollow];
    }
}

$offsetInicial = count($seccionesIniciales);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koalicius - Inicio personalizado</title>
    <link rel="icon" type="image/x-icon" href="assets/img/koali.ico">
    <link rel="stylesheet" href="assets/css/global.css?v=5.9">
    <link rel="stylesheet" href="assets/css/index.css?v=5.9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php require_once $rootPath . 'includes/header.php'; ?>
<main class="tendencias-main">
    <div id="secciones-container" data-offset="<?= $offsetInicial ?>">
        <?php foreach ($seccionesIniciales as $seccion): ?>
            <section class="bloque seccion-personalizada">
                <div class="seccion-header">
                    <div class="titulo-box">
                        <h2 class="titulo-seccion"><?= htmlspecialchars($seccion['nombre']) ?></h2>
                    </div>
                    <div class="carrusel-nav">
                        <button class="carrusel-btn carrusel-prev"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="carrusel-btn carrusel-next"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="carrusel-contenedor">
                    <div class="carrusel-track">
                        <?php foreach ($seccion['recetas'] as $receta):
                            $imagen = $baseUrl . $receta['imagen'];
                            $avatarUrl = $baseUrl . 'uploads/perfiles/perfil_' . $receta['autor_id'] . '.webp';
                        ?>
                            <article class="receta-card">
                                <a href="receta.php?id=<?= (int)$receta['id'] ?>" class="card-link">
                                    <div class="card-image">
                                        <img src="<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($receta['titulo']) ?>">
                                        <span class="tiempo-badge"><i class="fa-regular fa-clock"></i> <?= (int)$receta['tiempo_preparacion'] ?> min</span>
                                    </div>
                                    <div class="card-body">
                                        <h3><?= htmlspecialchars($receta['titulo']) ?></h3>
                                        <p class="descripcion"><?= htmlspecialchars(substr($receta['descripcion'], 0, 100)) ?>...</p>
                                        <div class="card-footer">
                                            <div class="autor">
                                                <img src="<?= htmlspecialchars($avatarUrl) ?>" class="avatar-mini" alt="avatar" onerror="this.onerror=null;this.src='<?= $baseUrl ?>assets/img/koali.ico'">
                                                <span><?= htmlspecialchars($receta['autor_nombre']) ?></span>
                                            </div>
                                            <div class="stats"><span><i class="fa-regular fa-eye"></i> <?= number_format($receta['total_vistas']) ?></span></div>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <div id="loading-spinner" class="loading-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando mas recetas...</div>
</main>
<div class="footer-simple"><p>&copy; 2026 Koalicius. Todos los derechos reservados.</p></div>
<script src="assets/js/global.js?v=5.9"></script>
<script src="assets/js/index.js?v=5.9"></script>
</body>
</html>