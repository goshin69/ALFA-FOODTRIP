<?php
session_start();
require_once '../includes/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Receta no válida');
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT r.*, u.nombre as autor, u.id as autor_id, u.imagen_perfil as autor_foto
                       FROM recetas r
                       JOIN usuarios u ON r.usuario_id = u.id
                       WHERE r.id = ? AND r.estado = 1");
$stmt->execute([$id]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    die('Receta no encontrada');
}

// Obtener imágenes
$stmt = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden ASC");
$stmt->execute([$id]);
$imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener etiquetas
$stmt = $pdo->prepare("SELECT e.nombre FROM etiquetas e
                       JOIN recetas_etiquetas re ON e.id = re.etiqueta_id
                       WHERE re.receta_id = ?");
$stmt->execute([$id]);
$etiquetas = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($receta['titulo']) ?> - Koalicius</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css?v=5.4">
    <link rel="stylesheet" href="assets/css/receta.css?v=5.4">
</head>
<body>
    <main class="receta-detalle">
        <div class="receta-header">
            <h1><?= htmlspecialchars($receta['titulo']) ?></h1>
            <div class="autor-info">
                <img src="<?= !empty($receta['autor_foto']) ? htmlspecialchars($receta['autor_foto']) : 'imageness/Logo Sesion.png' ?>" alt="Avatar">
                <a href="perfil.php?id=<?= $receta['autor_id'] ?>"><?= htmlspecialchars($receta['autor']) ?></a>
                <span class="fecha">Publicado el <?= date('d/m/Y', strtotime($receta['fecha_publicacion'])) ?></span>
            </div>
        </div>

        <div class="receta-meta">
            <span><i class="fa-regular fa-clock"></i> <?= $receta['tiempo_preparacion'] ?> min</span>
            <span><i class="fa-regular fa-chart-line"></i> Dificultad: <?= ucfirst($receta['dificultad']) ?></span>
        </div>

        <div class="receta-descripcion">
            <h2>Descripción</h2>
            <p><?= nl2br(htmlspecialchars($receta['descripcion'])) ?></p>
        </div>

        <div class="receta-ingredientes">
            <h2>Ingredientes</h2>
            <pre><?= htmlspecialchars($receta['ingredientes']) ?></pre>
        </div>

        <div class="receta-preparacion">
            <h2>Preparación</h2>
            <?= nl2br(htmlspecialchars($receta['preparacion'])) ?>
        </div>

        <?php if (!empty($imagenes)): ?>
        <div class="receta-imagenes">
            <h2>Galería</h2>
            <div class="galeria">
                <?php foreach ($imagenes as $img): ?>
                    <?php if (preg_match('/\.(mp4|avi|mov|mkv|webm)$/i', $img)): ?>
                        <video controls src="<?= htmlspecialchars($img) ?>"></video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Imagen de receta">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="receta-etiquetas">
            <h2>Etiquetas</h2>
            <div class="etiquetas-lista">
                <?php foreach ($etiquetas as $et): ?>
                    <span class="etiqueta"><?= htmlspecialchars($et) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>