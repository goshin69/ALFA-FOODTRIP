<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

try {
    $sql = "
        SELECT r.*, u.nombre as autor_nombre, u.rol as autor_rol
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        ORDER BY 
            CASE u.rol 
                WHEN 'restaurante' THEN 1
                WHEN 'usuario' THEN 2
                ELSE 3
            END,
            r.fecha_publicacion DESC
    ";

    $stmt = $pdo->query($sql);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recetas as &$receta) {
        $stmt_img = $pdo->prepare("SELECT ruta FROM imagenes WHERE receta_id = ? ORDER BY orden");
        $stmt_img->execute([$receta['id']]);
        $imagenes = $stmt_img->fetchAll(PDO::FETCH_COLUMN);
        $receta['imagenes'] = $imagenes;
    }

    echo json_encode(['ok' => true, 'recetas' => $recetas], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
