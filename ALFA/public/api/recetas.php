<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';

$sql = "
    SELECT r.id, r.titulo, r.descripcion, u.nombre as autor_nombre, u.rol as autor_rol,
           (SELECT GROUP_CONCAT(ruta) FROM imagenes WHERE receta_id = r.id ORDER BY orden) as imagenes
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

// Procesar imágenes (convertir GROUP_CONCAT en array)
foreach ($recetas as &$r) {
    $r['imagenes'] = $r['imagenes'] ? explode(',', $r['imagenes']) : [];
}

echo json_encode(['ok' => true, 'recetas' => $recetas]);
?>