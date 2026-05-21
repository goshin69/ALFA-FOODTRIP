<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/database.php';

function contieneGroseriasRegistro($texto) {
    $groserias = [
        'puta', 'puto', 'pendejo', 'pendeja', 'cabron', 'cabrona', 'verga',
        'vergas', 'chingar', 'chinga', 'chingue', 'chingada', 'chinguen',
        'culero', 'culera', 'culeros', 'culeras', 'hijueputa', 'hijueputas',
        'malparido', 'malparida', 'gonorrea', 'sapo', 'sapa', 'marica',
        'marico', 'maricon', 'maricona', 'maricones', 'guey', 'wey', 'güey',
        'pendejada', 'pendejadas', 'chingadera', 'chingaderas', 'madre',
        'madres', 'coño', 'coños', 'carajo', 'carajos', 'mierda', 'mierdas',
        'joder', 'jodido', 'jodida', 'jodidos', 'jodidas', 'follar', 'follada',
        'follado', 'follados', 'folladas', 'coger', 'cogiendo', 'cogida',
        'cogido', 'putear', 'puteando', 'putazo', 'putazos', 'culo', 'culos',
        'pedo', 'joto', 'pedos', 'pedorra', 'pedorro', 'pendejeando', 'pendejear'
    ];

    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace(
        ['á','é','í','ó','ú','ü','ñ'],
        ['a','e','i','o','u','u','n'],
        $texto
    );
    $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);

    foreach ($groserias as $palabra) {
        if (strpos($texto, $palabra) !== false) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? '';

if ($nombre === '' || $email === '' || $password === '' || $rol === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Todos los campos son obligatorios']);
    exit;
}

if (contieneGroseriasRegistro($nombre)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El nombre contiene palabras inapropiadas']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Correo no válido']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}
if (preg_match_all('/\d/', $password) < 2) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe contener al menos dos números']);
    exit;
}
if (preg_match_all('/[^a-zA-Z0-9\s]/', $password) < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe contener al menos un carácter especial']);
    exit;
}

if (!in_array($rol, ['usuario', 'restaurante'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Rol no válido']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'El correo ya está registrado']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)');
if ($stmt->execute([$nombre, $email, $hash, $rol])) {
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(500);
echo json_encode(['ok' => false, 'error' => 'Error interno al registrar']);
?>