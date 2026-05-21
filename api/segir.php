<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// Recibir datos
$seguidor_id = $_SESSION['usuario_id'];
$seguido_id = isset($_POST['seguido_id']) ? (int)$_POST['seguido_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validar datos
if ($seguido_id == 0 || !in_array($action, ['follow', 'unfollow'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

// No permitir seguirse a uno mismo
if ($seguidor_id == $seguido_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No puedes seguirte a ti mismo']);
    exit;
}

try {
    if ($action === 'follow') {
        // Verificar que no ya lo sigue
        $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmt->execute([$seguidor_id, $seguido_id]);
        if (!$stmt->fetchColumn()) {
            // Insertar relación de seguimiento
            $stmt = $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
            $stmt->execute([$seguidor_id, $seguido_id]);

            // Incrementar contador en usuarios (si existe la columna)
            $stmt = $pdo->prepare("UPDATE usuarios SET seguidores_count = seguidores_count + 1 WHERE id = ?");
            $stmt->execute([$seguido_id]);
        }
    } else {
        // Eliminar relación de seguimiento
        $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmt->execute([$seguidor_id, $seguido_id]);

        // Decrementar contador en usuarios (si existe la columna)
        $stmt = $pdo->prepare("UPDATE usuarios SET seguidores_count = GREATEST(seguidores_count - 1, 0) WHERE id = ?");
        $stmt->execute([$seguido_id]);
    }

    // Obtener contador actualizado directamente de usuarios (o de la tabla seguidores si no existe columna)
    $stmt = $pdo->prepare("SELECT seguidores_count FROM usuarios WHERE id = ?");
    $stmt->execute([$seguido_id]);
    $seguidores_count = $stmt->fetchColumn();
    if ($seguidores_count === false) {
        // Fallback: contar directamente de la tabla seguidores
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?");
        $stmt->execute([$seguido_id]);
        $seguidores_count = $stmt->fetchColumn();
    }

    // Verificar si ahora está siguiendo
    $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $siguiendo = (bool)$stmt->fetchColumn();

    echo json_encode([
        'ok' => true,
        'seguidores' => $seguidores_count,
        'siguiendo' => $siguiendo
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error en la base de datos']);
}