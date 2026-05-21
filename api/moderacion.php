<?php
// api/moderacion.php
session_start();
require_once '../../includes/database.php';
require_once '../../includes/funciones.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'moderador'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}

if ($method === 'GET' && isset($_GET['tab'])) {
    $tab = $_GET['tab'];
    $response = ['ok' => true, 'items' => [], 'counts' => []];

    try {
        switch ($tab) {
            case 'reportes':
                $stmt = $pdo->prepare("
                    SELECT rep.id, rep.receta_id, rec.titulo, 
                           LEFT(rec.descripcion, 100) AS descripcion_corta,
                           CONCAT(rep.tipo, IFNULL(CONCAT(': ', rep.detalle), '')) AS detalle,
                           rep.fecha_reporte AS fecha,
                           u.nombre AS usuario_reportante
                    FROM reportes rep
                    JOIN recetas rec ON rep.receta_id = rec.id
                    LEFT JOIN usuarios u ON rep.usuario_id = u.id
                    WHERE rep.estado = 'pendiente'
                    ORDER BY rep.fecha_reporte DESC
                ");
                $stmt->execute();
                $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'revision':
                $stmt = $pdo->prepare("
                    SELECT id, titulo, descripcion as descripcion_corta, fecha_publicacion as fecha,
                           'En revisión' as estado
                    FROM recetas
                    WHERE estado = 2
                    ORDER BY fecha_publicacion DESC
                ");
                $stmt->execute();
                $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'apelaciones':
                $stmt = $pdo->prepare("
                    SELECT id, titulo, descripcion as descripcion_corta, fecha_eliminacion as fecha,
                           'Apelación pendiente' as estado
                    FROM recetas
                    WHERE estado = 3 AND estado_apelacion = 'pendiente'
                    ORDER BY fecha_eliminacion DESC
                ");
                $stmt->execute();
                $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'sanciones':
                $stmt = $pdo->prepare("
                    SELECT id as usuario_id, nombre as nombre_usuario, email, fecha_ban as fecha,
                           'Suspendido' as estado
                    FROM usuarios
                    WHERE baneado = 1
                    ORDER BY fecha_ban DESC
                ");
                $stmt->execute();
                $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }

        $counts = [];
        $stmt = $pdo->query("SELECT COUNT(*) FROM reportes WHERE estado = 'pendiente'");
        $counts['reportes'] = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM recetas WHERE estado = 2");
        $counts['revision'] = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM recetas WHERE estado = 3 AND estado_apelacion = 'pendiente'");
        $counts['apelaciones'] = $stmt->fetchColumn();
        $response['counts'] = $counts;

        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    $tab = $_POST['tab'] ?? '';

    try {
        switch ($action) {
            case 'aprobar':
                $stmt = $pdo->prepare("UPDATE reportes SET estado = 'revisado' WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['ok' => true]);
                break;
            case 'rechazar':
                $stmt = $pdo->prepare("UPDATE recetas SET estado = 3, estado_apelacion = 'pendiente', fecha_eliminacion = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                $pdo->prepare("UPDATE reportes SET estado = 'revisado' WHERE receta_id = ?")->execute([$id]);
                echo json_encode(['ok' => true]);
                break;
            case 'publicar':
                $stmt = $pdo->prepare("UPDATE recetas SET estado = 1 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['ok' => true]);
                break;
            case 'eliminar':
                eliminarRecetaDefinitiva($pdo, $id);
                echo json_encode(['ok' => true]);
                break;
            case 'aceptar-apelacion':
                $stmt = $pdo->prepare("UPDATE recetas SET estado = 1, estado_apelacion = 'aprobada', fecha_eliminacion = NULL WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['ok' => true]);
                break;
            case 'rechazar-apelacion':
                $stmt = $pdo->prepare("UPDATE recetas SET estado_apelacion = 'rechazada' WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['ok' => true]);
                break;
            case 'levantar-sancion':
                $usuario_id = $_POST['usuario_id'] ?? 0;
                $stmt = $pdo->prepare("UPDATE usuarios SET baneado = 0, fecha_ban = NULL WHERE id = ?");
                $stmt->execute([$usuario_id]);
                echo json_encode(['ok' => true]);
                break;
            case 'reportar_infraccion':
                $receta_id = $_POST['receta_id'] ?? 0;
                $tipo = $_POST['tipo'] ?? 'derechos_autor';
                $detalle = $_POST['detalle'] ?? '';
                $usuario_id = $_SESSION['usuario_id'];

                if (!$receta_id) {
                    echo json_encode(['ok' => false, 'error' => 'ID de receta requerido']);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT 1 FROM reportes WHERE usuario_id = ? AND receta_id = ? AND estado = 'pendiente'");
                $stmt->execute([$usuario_id, $receta_id]);
                if ($stmt->fetchColumn()) {
                    echo json_encode(['ok' => false, 'error' => 'Ya has reportado esta receta recientemente']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO reportes (usuario_id, receta_id, tipo, detalle) VALUES (?, ?, ?, ?)");
                $stmt->execute([$usuario_id, $receta_id, $tipo, $detalle]);

                echo json_encode(['ok' => true, 'mensaje' => 'Reporte enviado correctamente']);
                break;
            default:
                echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Solicitud inválida']);