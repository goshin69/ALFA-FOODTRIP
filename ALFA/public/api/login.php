<?php
session_start();
require_once '../../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Campos incompletos']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, email, password, imagen_perfil, rol FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Credenciales incorrectas']);
    exit;
}

unset($usuario['password']);
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_rol'] = $usuario['rol'];

echo json_encode([
    'ok' => true,
    'user' => [
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => $usuario['email'],
        'imagen_perfil' => $usuario['imagen_perfil'],
        'rol' => $usuario['rol']
    ]
]);