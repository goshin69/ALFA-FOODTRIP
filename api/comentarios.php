<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$receta_id = (int)($_POST['receta_id'] ?? $_GET['receta_id'] ?? 0);

if (!$receta_id && $action !== 'comentarios') {
    echo json_encode(['ok' => false, 'error' => 'ID de receta requerido']);
    exit;
}

// ---------- OBTENER COMENTARIOS ----------
if ($action === 'comentarios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.id, c.contenido, c.fecha, c.parent_id, u.nombre as usuario_nombre
        FROM comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.receta_id = ? AND c.estado = 1
        ORDER BY c.fecha ASC
    ");
    $stmt->execute([$receta_id]);
    echo json_encode(['ok' => true, 'comentarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ---------- NUEVO COMENTARIO ----------
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
    echo json_encode(['ok' => $ok]);
    exit;
}

// ---------- LIKE / FAVORITO ----------
if (in_array($action, ['like', 'favorite']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'Inicia sesión']);
        exit;
    }
    $tabla = ($action === 'like') ? 'me_gusta' : 'favoritos';
    // Verificar si ya existe
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
    // Contar total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tabla WHERE receta_id = ?");
    $stmt->execute([$receta_id]);
    $total = $stmt->fetchColumn();
    echo json_encode(['ok' => true, 'activo' => $activo, 'total' => $total]);
    exit;
}

// ---------- DENUNCIAR RECETA (mejorado) ----------
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
    // Evitar denuncias duplicadas del mismo usuario para esta receta
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

// ---------- ELIMINAR RECETA (autor o admin/moderador) ----------
if ($action === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
    // Obtener dueño de la receta
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
    // Eliminación física (también puedes hacer soft delete cambiando estado a 3)
    $stmt = $pdo->prepare("DELETE FROM recetas WHERE id = ?");
    $ok = $stmt->execute([$receta_id]);
    echo json_encode(['ok' => $ok]);
    exit;
}

// Si ninguna acción coincide
echo json_encode(['ok' => false, 'error' => 'Acción no válida']);