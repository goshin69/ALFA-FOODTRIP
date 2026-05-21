<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$receta_id = (int)($_POST['receta_id'] ?? $_GET['receta_id'] ?? 0);

if (!$receta_id && !in_array($action, ['comentarios', 'denunciar_comentario', 'eliminar_comentario'])) {
    echo json_encode(['ok' => false, 'error' => 'ID de receta requerido']);
    exit;
}

if ($action === 'comentarios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.id, c.contenido, c.fecha, c.parent_id, u.nombre as usuario_nombre, u.id as usuario_id
        FROM comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.receta_id = ? AND c.estado = 1
        ORDER BY c.fecha ASC
    ");
    $stmt->execute([$receta_id]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'comentarios' => $comentarios]);
    exit;
}

if ($action === 'comentar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
    $contenido = trim($_POST['contenido'] ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    if (empty($contenido)) {
        echo json_encode(['ok' => false, 'error' => 'Comentario vacío']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO comentarios (receta_id, usuario_id, contenido, parent_id, fecha) VALUES (?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$receta_id, $_SESSION['usuario_id'], $contenido, $parent_id]);
    if ($ok && $parent_id > 0) {
        $stmtPadre = $pdo->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
        $stmtPadre->execute([$parent_id]);
        $padre = $stmtPadre->fetch(PDO::FETCH_ASSOC);
        if ($padre && $padre['usuario_id'] != $_SESSION['usuario_id']) {
            $nombre = $_SESSION['usuario_nombre'] ?? 'Alguien';
            $mensaje = "$nombre ha respondido a tu comentario.";
            $stmtNotif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, referencia_id, mensaje, fecha) VALUES (?, 'respuesta_comentario', ?, ?, NOW())");
            $stmtNotif->execute([$padre['usuario_id'], $parent_id, $mensaje]);
        }
    }
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'denunciar_comentario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
    $comentario_id = (int)($_POST['comentario_id'] ?? 0);
    $motivo = $_POST['motivo'] ?? '';
    $detalle = $_POST['detalle'] ?? '';
    if (!$comentario_id || empty($motivo)) {
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    $tipos_validos = ['acoso', 'spam', 'inapropiado', 'odio', 'falso', 'otro'];
    if (!in_array($motivo, $tipos_validos)) {
        echo json_encode(['ok' => false, 'error' => 'Motivo inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM reportes_comentarios WHERE comentario_id = ? AND usuario_id = ?");
    $stmt->execute([$comentario_id, $_SESSION['usuario_id']]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['ok' => false, 'error' => 'Ya has denunciado este comentario']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO reportes_comentarios (comentario_id, usuario_id, motivo, detalle, fecha) VALUES (?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$comentario_id, $_SESSION['usuario_id'], $motivo, $detalle]);
    echo json_encode(['ok' => $ok, 'mensaje' => 'Denuncia enviada. Gracias.']);
    exit;
}

if ($action === 'eliminar_comentario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
    $comentario_id = (int)($_POST['comentario_id'] ?? 0);
    if (!$comentario_id) {
        echo json_encode(['ok' => false, 'error' => 'ID de comentario requerido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
    $stmt->execute([$comentario_id]);
    $comentario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$comentario) {
        echo json_encode(['ok' => false, 'error' => 'Comentario no existe']);
        exit;
    }
    $es_autor = ($comentario['usuario_id'] == $_SESSION['usuario_id']);
    $es_mod = in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'moderador']);
    if (!$es_autor && !$es_mod) {
        echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
    $ok = $stmt->execute([$comentario_id]);
    echo json_encode(['ok' => $ok]);
    exit;
}

if (in_array($action, ['like', 'favorite']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'Inicia sesión']);
        exit;
    }
    $tabla = ($action === 'like') ? 'me_gusta' : 'favoritos';
    $stmt = $pdo->prepare("SELECT 1 FROM $tabla WHERE usuario_id = ? AND receta_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $receta_id]);
    $existe = $stmt->fetchColumn();
    if ($existe) {
        $stmt = $pdo->prepare("DELETE FROM $tabla WHERE usuario_id = ? AND receta_id = ?");
        $stmt->execute([$_SESSION['usuario_id'], $receta_id]);
        $activo = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO $tabla (usuario_id, receta_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $receta_id]);
        $activo = true;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tabla WHERE receta_id = ?");
    $stmt->execute([$receta_id]);
    $total = $stmt->fetchColumn();
    echo json_encode(['ok' => true, 'activo' => $activo, 'total' => $total]);
    exit;
}

if ($action === 'denunciar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'Inicia sesión']);
        exit;
    }
    $motivo = $_POST['motivo'] ?? '';
    $detalle = $_POST['detalle'] ?? '';
    $tipos_validos = ['acoso', 'spam', 'inapropiado', 'derechos_autor', 'violencia', 'suplantacion', 'otro'];
    if (!in_array($motivo, $tipos_validos)) {
        echo json_encode(['ok' => false, 'error' => 'Motivo inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM reportes WHERE usuario_id = ? AND receta_id = ? AND estado = 'pendiente'");
    $stmt->execute([$_SESSION['usuario_id'], $receta_id]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['ok' => false, 'error' => 'Ya has denunciado esta receta recientemente']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO reportes (usuario_id, receta_id, tipo, detalle, fecha_reporte) VALUES (?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$_SESSION['usuario_id'], $receta_id, $motivo, $detalle]);
    echo json_encode(['ok' => $ok, 'mensaje' => 'Denuncia enviada. Gracias por ayudar.']);
    exit;
}

if ($action === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT usuario_id FROM recetas WHERE id = ?");
    $stmt->execute([$receta_id]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receta) {
        echo json_encode(['ok' => false, 'error' => 'Receta no existe']);
        exit;
    }
    $es_autor = ($receta['usuario_id'] == $_SESSION['usuario_id']);
    $es_mod = in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'moderador']);
    if (!$es_autor && !$es_mod) {
        echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
    $ok = $stmt->execute([$receta_id]);
    echo json_encode(['ok' => $ok]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);