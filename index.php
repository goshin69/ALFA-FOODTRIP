<?php
require_once 'includes/verificar_sesion.php';
require_once 'database.php';

$sql = "
    SELECT r.*, u.nombre as autor_nombre, u.rol as autor_rol
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    ORDER BY 
        CASE u.rol 
            WHEN 'restaurante' THEN 1
            WHEN 'usuario' THEN 2
            ELSE 3
        END,
        r.fecha_publicacion DESC
";
$stmt = $pdo->query($sql);
$recetas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Recetas</title>
    <style>
        .receta { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .restaurante { background-color: #fff3e0; border-left: 5px solid #ff9800; }
        .imagenes img { max-width: 150px; margin-right: 10px; border-radius: 5px; }
        .autor { font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h1>
    <nav>
        <a href="crear_receta.php">➕ Publicar receta</a> |
        <a href="logout.php">🚪 Cerrar sesión</a>
    </nav>
    <hr>

    <h2>Recetas recientes</h2>

    <?php if (count($recetas) === 0): ?>
        <p>Aún no hay recetas. ¡Sé el primero en publicar!</p>
    <?php endif; ?>

    <?php foreach ($recetas as $receta): ?>
        <?php
        $stmt_img = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden");
        $stmt_img->execute([$receta['id']]);
        $imagenes = $stmt_img->fetchAll();

        $clase_extra = ($receta['autor_rol'] === 'restaurante') ? 'restaurante' : '';
        ?>
        <div class="receta <?= $clase_extra ?>">
            <h3><?= htmlspecialchars($receta['titulo']) ?></h3>
            <p class="autor">
                Publicado por: <?= htmlspecialchars($receta['autor_nombre']) ?> 
                (<?= $receta['autor_rol'] ?>)
            </p>
            <p><?= nl2br(htmlspecialchars($receta['descripcion'])) ?></p>
            
            <?php if (count($imagenes) > 0): ?>
                <div class="imagenes">
                    <?php foreach ($imagenes as $img): ?>
                        <?php $ext = strtolower(pathinfo($img['ruta'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($ext, ['mp4','webm','ogg'])): ?>
                            <video controls style="max-width:100%;"><source src="<?= htmlspecialchars($img['ruta']) ?>" type="video/<?= htmlspecialchars($ext) ?>"></video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($img['ruta']) ?>" alt="Imagen de receta">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <p><a href="receta.php?id=<?= $receta['id'] ?>">Ver detalles y comentar</a></p>
        </div>
    <?php endforeach; ?>
</body>
</html>