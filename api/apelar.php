<?php
// apelar.php
session_start();
require_once 'includes/database.php';
require_once 'includes/funciones.php';
$baseUrl = '/ALFA/public/';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . $baseUrl . 'login.php');
    exit;
}

$receta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$receta_id) {
    die('ID de receta no válido');
}

$stmt = $pdo->prepare("SELECT id, titulo, estado, estado_apelacion FROM recetas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$receta_id, $_SESSION['usuario_id']]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta || $receta['estado'] != 3) {
    die('No puedes apelar esta receta o ya no está eliminada.');
}

if ($receta['estado_apelacion'] === 'pendiente') {
    die('Ya tienes una apelación pendiente para esta receta.');
}

if ($receta['estado_apelacion'] === 'aprobada') {
    die('Esta receta ya fue restaurada.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');
    if (empty($motivo)) {
        $error = 'Debes escribir un motivo de apelación.';
    } else {
        $stmt = $pdo->prepare("UPDATE recetas SET estado_apelacion = 'pendiente', motivo_apelacion = ? WHERE id = ?");
        $stmt->execute([$motivo, $receta_id]);
        header('Location: ' . $baseUrl . 'perfil.php?id=' . $_SESSION['usuario_id'] . '?msg=apelacion_enviada');
        exit;
    }
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Apelar receta - Koalicius</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="apelacion-container" style="max-width: 600px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 16px;">
        <h1>Apelar eliminación de receta</h1>
        <p><strong><?= htmlspecialchars($receta['titulo']) ?></strong> fue eliminada por un moderador. Si consideras que fue un error, explica por qué debería ser restaurada.</p>
        <?php if ($error): ?>
            <div class="alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="campo">
                <label for="motivo">Motivo de apelación:</label>
                <textarea name="motivo" id="motivo" rows="5" required placeholder="Explica detalladamente por qué la receta no infringe las normas..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn-primary">Enviar apelación</button>
                <a href="<?= $baseUrl ?>perfil.php?id=<?= $_SESSION['usuario_id'] ?>" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </main>
</body>
</html>