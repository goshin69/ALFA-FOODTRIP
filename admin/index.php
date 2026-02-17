<?php
require_once '../includes/verificar_sesion.php';
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
?>
<h1>Panel de Administración</h1>
<p>Bienvenido, <?= $_SESSION['usuario_nombre'] ?></p>
<ul>
    <li><a href="usuarios.php">Gestionar Usuarios</a></li>
    <!-- Aquí luego añadirás más opciones -->
</ul>
<a href="../logout.php">Cerrar sesión</a>