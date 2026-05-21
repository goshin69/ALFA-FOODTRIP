<?php
header('Content-Type: application/json');
require_once '../../includes/database.php';

$etiquetas_importantes = [
    ['id' => 9, 'nombre' => 'Caldo'],
    ['id' => 15, 'nombre' => 'Sopas'],
    ['id' => 18, 'nombre' => 'Tacos'],
    ['id' => 10, 'nombre' => 'Pastas'],
    ['id' => 7, 'nombre' => 'Japonesa'],
    ['id' => 16, 'nombre' => 'Ensaladas'],
];

$secciones = [];

// 1. Sección "Lo más nuevo" (siempre presente si hay recetas)
$stmt = $pdo->prepare("
    SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, u.nombre as autor_nombre, u.id as autor_id,
           (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen,
           r.tiempo_preparacion, r.dificultad
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.estado = 1
    ORDER BY r.fecha_publicacion DESC
    LIMIT 10
");
$stmt->execute();
$recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($recientes) > 0) {
    foreach ($recientes as &$receta) {
        $receta['descripcion'] = nl2br(htmlspecialchars($receta['descripcion']));
        if (!$receta['imagen']) {
            $receta['imagen'] = 'imageness/default_receta.jpg';
        }
    }
    $secciones[] = [
        'nombre' => 'Lo más nuevo',
        'etiqueta_id' => 0,
        'recetas' => $recientes
    ];
}

// 2. Secciones por etiquetas importantes
foreach ($etiquetas_importantes as $etiqueta) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.titulo, r.descripcion, r.fecha_publicacion, u.nombre as autor_nombre, u.id as autor_id,
               (SELECT MIN(ruta) FROM imagenes WHERE receta_id = r.id) as imagen,
               r.tiempo_preparacion, r.dificultad
        FROM recetas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN recetas_etiquetas re ON r.id = re.receta_id
        WHERE re.etiqueta_id = ? AND r.estado = 1
        ORDER BY r.fecha_publicacion DESC
        LIMIT 10
    ");
    $stmt->execute([$etiqueta['id']]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($recetas) > 0) {
        foreach ($recetas as &$receta) {
            $receta['descripcion'] = nl2br(htmlspecialchars($receta['descripcion']));
            if (!$receta['imagen']) {
                $receta['imagen'] = 'imageness/default_receta.jpg';
            }
        }
        $secciones[] = [
            'nombre' => $etiqueta['nombre'],
            'etiqueta_id' => $etiqueta['id'],
            'recetas' => $recetas
        ];
    }
}

echo json_encode(['ok' => true, 'secciones' => $secciones]);