<?php
/**
 * Archivo de conexión a la Base de Datos
 * Database: alimentos
 * Server: localhost
 */

// Configuración de la base de datos
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'alimentos');

// Crear conexión
$conexion = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configurar charset a utf8mb4
$conexion->set_charset("utf8mb4");

// Función para ejecutar consultas SELECT
function obtenerDatos($sql, $tipos = "", $parametros = []) {
    global $conexion;

    $stmt = $conexion->prepare($sql);

    if ($tipos && $parametros) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    return $resultado;
}

// Función para ejecutar INSERT, UPDATE, DELETE
function ejecutarConsulta($sql, $tipos = "", $parametros = []) {
    global $conexion;

    $stmt = $conexion->prepare($sql);

    if ($tipos && $parametros) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
}

// Función para obtener el ID de la última inserción
function obtenerUltimoId() {
    global $conexion;
    return $conexion->insert_id;
}

// Función para cerrar la conexión
function cerrarConexion() {
    global $conexion;
    $conexion->close();
}

?>
