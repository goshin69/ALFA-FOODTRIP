<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/database.php';

$term = $_GET['q'] ?? '';
if (strlen($term) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre FROM etiquetas WHERE nombre LIKE ? ORDER BY nombre LIMIT 10");
$stmt->execute(["$term%"]);
$etiquetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($etiquetas);