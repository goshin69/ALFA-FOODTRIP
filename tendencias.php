<?php
include 'includes/header.php';
require_once 'includes/database.php';

/* 🔥 CONSULTA: recetas más populares */
$stmt = $pdo->query("
    SELECT r.id, r.titulo, r.imagen, 
           COUNT(l.id) AS total_likes
    FROM recetas r
    LEFT JOIN likes l ON r.id = l.receta_id
    GROUP BY r.id
    ORDER BY total_likes DESC
    LIMIT 10
");

$recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tendencias</title>

<link rel="stylesheet" href="assets/css/global.css">
<link rel="stylesheet" href="assets/css/tendencias.css">

</head>

<body>

<div class="tendencias-container">

  <h1>🔥 Tendencias</h1>

  <div class="tendencias-grid">

    <?php foreach($recetas as $r): ?>
      
      <div class="t-card">
        <img src="<?= !empty($r['imagen']) ? $r['imagen'] : 'https://via.placeholder.com/300x200' ?>">
        <h3><?= htmlspecialchars($r['titulo']) ?></h3>
        <p>❤️ <?= $r['total_likes'] ?> likes</p>
      </div>

    <?php endforeach; ?>

  </div>

</div>

</body>
</html>