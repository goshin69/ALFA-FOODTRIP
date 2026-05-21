<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/funciones.php';

$accion = $_POST['accion'] ?? '';

if (!in_array($accion, ['apelar', 'resolver'])) {
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    exit;
}

// --- Lógica para APELAR (creador) ---
if ($accion === 'apelar') {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $receta_id = isset($_POST['receta_id']) ? (int)$_POST['receta_id'] : 0;
    if ($receta_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT usuario_id, estado, fecha_eliminacion FROM recetas WHERE id = ?");
    $stmt->execute([$receta_id]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta || $receta['estado'] != 3) {
        echo json_encode(['ok' => false, 'error' => 'Receta no encontrada o no está en proceso de eliminación']);
        exit;
    }

    if ($receta['usuario_id'] != $_SESSION['usuario_id']) {
        echo json_encode(['ok' => false, 'error' => 'No eres el creador de esta receta']);
        exit;
    }

    $limite = new DateTime($receta['fecha_eliminacion']);
    $limite->modify('+30 days');
    if (new DateTime() > $limite) {
        echo json_encode(['ok' => false, 'error' => 'El plazo para apelar ha expirado']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE recetas SET estado_apelacion = 'pendiente' WHERE id = ?");
    $stmt->execute([$receta_id]);
    echo json_encode(['ok' => true]);
    exit;
}

// --- Lógica para RESOLVER (admin) ---
if ($accion === 'resolver') {
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Solo administradores']);
        exit;
    }

    $receta_id = isset($_POST['receta_id']) ? (int)$_POST['receta_id'] : 0;
    $decision = $_POST['decision'] ?? ''; // 'aceptar' o 'rechazar'

    if ($receta_id <= 0 || !in_array($decision, ['aceptar', 'rechazar'])) {
        echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }

    try {
        if ($decision === 'aceptar') {
            $pdo->prepare("UPDATE recetas SET estado = 1, estado_apelacion = 'aceptada', fecha_eliminacion = NULL, motivo_eliminacion = NULL WHERE id = ?")->execute([$receta_id]);
            $pdo->prepare("UPDATE strikes SET estado = 'resuelto' WHERE receta_id = ? AND estado = 'activo'")->execute([$receta_id]);

            $stmt = $pdo->prepare("SELECT titulo, usuario_id FROM recetas WHERE id = ?");
            $stmt->execute([$receta_id]);
            $receta_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($receta_data) {
                crearNotificacion($pdo, $receta_data['usuario_id'], 'apelacion_aceptada', "Tu apelación para la receta '{$receta_data['titulo']}' ha sido aceptada. La receta ha sido restaurada.", $receta_id);
            }
            echo json_encode(['ok' => true]);
        } else { // rechazar
            $stmt = $pdo->prepare("SELECT titulo, usuario_id FROM recetas WHERE id = ?");
            $stmt->execute([$receta_id]);
            $receta_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($receta_data) {
                eliminarRecetaDefinitiva($pdo, $receta_id);
                crearNotificacion($pdo, $receta_data['usuario_id'], 'apelacion_rechazada', "Tu apelación para la receta '{$receta_data['titulo']}' ha sido rechazada. La receta ha sido eliminada definitivamente.", $receta_id);
            }
            echo json_encode(['ok' => true]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
    }
}