<?php
// Incluir conexión a la base de datos
require_once '../../conexion.php';

header('Content-Type: application/json');

// Validar que es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$usuario_id = isset($_POST['id']) ? intval($_POST['id']) : null;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;

// Validar datos
if (!$usuario_id || !$nombre || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Validar que el usuario existe
$sql_check = "SELECT id FROM usuarios WHERE id = ?";
$resultado_check = obtenerDatos($sql_check, "i", [$usuario_id]);

if ($resultado_check->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Actualizar el perfil
$sql_update = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";

if (ejecutarConsulta($sql_update, 'ssi', [$nombre, $email, $usuario_id])) {
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil']);
}

cerrarConexion();
?>
