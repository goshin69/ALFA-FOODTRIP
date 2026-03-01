<?php // <-----Eliminalo---->
require_once 'includes/verificar_sesion.php';
require_once 'database.php';

$receta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($receta_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, u.nombre AS autor_nombre, u.rol AS autor_rol FROM recetas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.id = ?");
$stmt->execute([$receta_id]);
$receta = $stmt->fetch();

if (!$receta) {
    header('Location: index.php');
    exit;
}

$stmt_img = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden");
$stmt_img->execute([$receta_id]);
$imagenes = $stmt_img->fetchAll();

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comentario = trim($_POST['comentario']);
    $puntuacion = (int)$_POST['puntuacion'];
    $usuario_id = $_SESSION['usuario_id'];

    if (empty($comentario)) {
        $mensaje = "El comentario no puede estar vacío.";
    } elseif ($puntuacion < 0 || $puntuacion > 5) {
        $mensaje = "La puntuación debe ser entre 0 y 5 estrellas.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO comentarios (receta_id, usuario_id, comentario, puntuacion) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$receta_id, $usuario_id, $comentario, $puntuacion])) {
            $mensaje = "Comentario publicado correctamente.";
        } else {
            $mensaje = "Error al publicar el comentario.";
        }
    }
}

$stmt_com = $pdo->prepare("SELECT c.*, u.nombre AS usuario_nombre FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.receta_id = ? ORDER BY c.fecha DESC");
$stmt_com->execute([$receta_id]);
$comentarios = $stmt_com->fetchAll();

$promedio = 0;
if (count($comentarios) > 0) {
    $suma = array_sum(array_column($comentarios, 'puntuacion'));
    $promedio = round($suma / count($comentarios), 1);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($receta['titulo']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1><?= htmlspecialchars($receta['titulo']) ?></h1>
    <p><strong>Publicado por:</strong> <?= htmlspecialchars($receta['autor_nombre']) ?> (<?= $receta['autor_rol'] ?>)</p>
    <p><strong>Fecha:</strong> <?= $receta['fecha_publicacion'] ?></p>
    
    <div class="receta-detalle">
        <p><?= nl2br(htmlspecialchars($receta['descripcion'])) ?></p>
        
        <?php if (count($imagenes) > 0): ?>
            <div class="imagenes-detalle">
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
    </div>

    <hr>

    <h2>Puntuación promedio: 
        <span class="promedio"><?= $promedio ?> / 5 ★</span>
    </h2>

    <h3>Comentarios (<?= count($comentarios) ?>)</h3>

    <?php if (count($comentarios) > 0): ?>
        <?php foreach ($comentarios as $com): ?>
            <div class="comentario">
                <p>
                    <strong><?= htmlspecialchars($com['usuario_nombre']) ?></strong> 
                    - <span class="puntuacion-estrellas">
                        <?= str_repeat('★', $com['puntuacion']) . str_repeat('☆', 5 - $com['puntuacion']) ?>
                    </span>
                    <br>
                    <small><?= $com['fecha'] ?></small>
                </p>
                <p><?= nl2br(htmlspecialchars($com['comentario'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aún no hay comentarios. Sé el primero en opinar.</p>
    <?php endif; ?>

    <hr>

    <h3>Deja tu comentario y puntuación</h3>

    <?php if ($mensaje): ?>
        <p class="<?= strpos($mensaje, 'correctamente') ? 'exito' : 'error' ?>">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="receta.php?id=<?= $receta_id ?>" class="form-comentario">
        <label for="puntuacion">Puntuación (0 a 5 estrellas):</label><br>
        <select name="puntuacion" id="puntuacion" required>
            <option value="">Selecciona</option>
            <option value="0">0 ★</option>
            <option value="1">1 ★</option>
            <option value="2">2 ★★</option>
            <option value="3">3 ★★★</option>
            <option value="4">4 ★★★★</option>
            <option value="5">5 ★★★★★</option>
        </select>
        <br><br>

        <label for="comentario">Comentario:</label><br>
        <textarea name="comentario" id="comentario" rows="4" cols="50" required></textarea>
        <br><br>

        <button type="submit">Publicar comentario</button>
    </form>

    <p><a href="index.html">← Volver al inicio</a></p>
</body>
</html>