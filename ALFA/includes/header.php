<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/database.php';

$logueado = false;
$usuario = null;

if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT id, nombre, imagen_perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) $logueado = true;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<header>
    <div class="header-top">
        <div class="logo">
            <a href="index.php">
                <img src="imageness/Koalii.png" alt="Koalicius">
            </a>
        </div>
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="Buscar recetas...">
        </div>
        <div class="user-profile">
            <a href="<?= $logueado ? 'perfil.php' : 'login.php' ?>">
                <img src="<?= $logueado && !empty($usuario['imagen_perfil']) ? $usuario['imagen_perfil'] : 'imageness/Logo Sesion.png' ?>" alt="Perfil">
                <span><?= $logueado ? htmlspecialchars($usuario['nombre']) : 'Inicia sesión' ?></span>
            </a>
        </div>
    </div>
    <nav class="header-nav">
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> Inicio</a>
        <a href="tendencia.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'tendencia.php' ? 'active' : '' ?>"><i class="fa-solid fa-fire"></i> Tendencia</a>
        <a href="videos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'videos.php' ? 'active' : '' ?>"><i class="fa-solid fa-play"></i> Videos</a>
        <a href="notificacion.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'notificacion.php' ? 'active' : '' ?>"><i class="fa-solid fa-bell"></i> Notificación</a>
        <a href="crear_receta.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'crear_receta.php' ? 'active' : '' ?>"><i class="fa-solid fa-utensils"></i> Crear Receta</a>
        <a href="configuracion.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> Configuración</a>
    </nav>
</header>