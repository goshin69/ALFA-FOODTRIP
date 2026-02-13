<?php
require_once '../includes/verificar_sesion.php';
require_once '../database.php';

// Solo admin puede acceder
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Procesar eliminación de usuario si se recibe el parámetro 'eliminar'
if (isset($_GET['eliminar'])) {
    $usuario_id = $_GET['eliminar'];

    // --- IMPORTANTE: Eliminar imágenes físicas del servidor ---
    // 1. Obtener todas las imágenes de las recetas de este usuario
    $stmt = $pdo->prepare("
        SELECT i.ruta FROM imagenes i
        JOIN recetas r ON i.receta_id = r.id
        WHERE r.usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $imagenes = $stmt->fetchAll();

    // 2. Borrar cada archivo físico
    foreach ($imagenes as $img) {
        $ruta_completa = $_SERVER['DOCUMENT_ROOT'] . '/PI/' . $img['ruta'];
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa); // elimina el archivo
        }
    }

    // 3. Eliminar el usuario (las recetas, comentarios e imágenes se borran automáticamente por CASCADE)
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);

    // Redirigir para evitar reenvío del formulario al refrescar
    header('Location: usuarios.php?mensaje=Usuario eliminado correctamente');
    exit;
}

// Obtener todos los usuarios
$stmt = $pdo->query("SELECT id, nombre, email, rol, fecha_registro FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-eliminar { color: red; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Panel de Administración</h1>
    <nav>
        <a href="index.php">Inicio</a> |
        <a href="usuarios.php">Usuarios</a> |
        <a href="../logout.php">Cerrar sesión</a>
    </nav>
    <h2>Lista de Usuarios</h2>

    <?php if (isset($_GET['mensaje'])): ?>
        <p style="color: green;"><?= htmlspecialchars($_GET['mensaje']) ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Fecha registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= $usuario['id'] ?></td>
                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                <td><?= htmlspecialchars($usuario['email']) ?></td>
                <td><?= $usuario['rol'] ?></td>
                <td><?= $usuario['fecha_registro'] ?></td>
                <td>
                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): // No puede eliminarse a sí mismo ?>
                        <a href="?eliminar=<?= $usuario['id'] ?>" 
                           onclick="return confirm('¿Estás seguro de eliminar este usuario? También se borrarán todas sus recetas, comentarios e imágenes.')"
                           class="btn-eliminar">Eliminar</a>
                    <?php else: ?>
                        (Tu usuario)
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>