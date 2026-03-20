# Conexión a Base de Datos - ALFA FOODTRIP

## Archivos Creados

### 1. `conexion.php`
Archivo principal de conexión a la base de datos. Contiene:
- Configuración de credenciales (servidor, usuario, contraseña, base de datos)
- Funciones seguras para ejecutar consultas con Prepared Statements
- Manejo de errores

### 2. `ejemplos_conexion.php`
Archivo con ejemplos prácticos de cómo usar la conexión.

## Cómo Usar

### En cualquier archivo PHP de tu proyecto:

```php
<?php
// Incluir la conexión
require 'conexion.php';

// Usar las funciones disponibles
?>
```

## Funciones Disponibles

### 1. `obtenerDatos($sql, $tipos = "", $parametros = [])`
Para consultas SELECT. Retorna un objeto resultado.

**Ejemplo:**
```php
$sql = "SELECT id, nombre, email FROM usuarios WHERE id = ?";
$resultado = obtenerDatos($sql, "i", [4]);

if ($resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    echo $fila['nombre'];
}
```

### 2. `ejecutarConsulta($sql, $tipos = "", $parametros = [])`
Para INSERT, UPDATE, DELETE. Retorna true si tuvo éxito.

**Ejemplo INSERT:**
```php
$sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
$password = password_hash('micontrasena', PASSWORD_BCRYPT);

if (ejecutarConsulta($sql, 'ssss', ['Juan', 'juan@email.com', $password, 'usuario'])) {
    $idNuevo = obtenerUltimoId();
    echo "Usuario creado con ID: " . $idNuevo;
}
```

**Ejemplo UPDATE:**
```php
$sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
if (ejecutarConsulta($sql, 'ssi', ['Juan Pérez', 'juan@example.com', 4])) {
    echo "Usuario actualizado";
}
```

**Ejemplo DELETE:**
```php
$sql = "DELETE FROM usuarios WHERE id = ?";
if (ejecutarConsulta($sql, "i", [4])) {
    echo "Usuario eliminado";
}
```

### 3. `obtenerUltimoId()`
Obtiene el ID de la última inserción.

**Ejemplo:**
```php
$idNuevo = obtenerUltimoId();
```

### 4. `cerrarConexion()`
Cierra la conexión con la base de datos.

```php
cerrarConexion();
```

## Tipos de Datos para bind_param

| Tipo | Significado |
|------|------------|
| `i` | Integer (entero) |
| `d` | Double/Float (decimal) |
| `s` | String (texto) |
| `b` | Blob (binario) |

**Combinar tipos:**
- `"isi"` = Integer, String, Integer
- `"sss"` = String, String, String
- `"ids"` = Integer, Double, String

## Seguridad

✅ **Protegido contra SQL Injection** - Usa Prepared Statements
✅ **UTF-8 configurado** - Soporta caracteres especiales
✅ **Manejo de errores** - Muestra errores de conexión

## Configuración de Conexión

Si necesitas cambiar los credenciales, edita el archivo `conexion.php`:

```php
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'alimentos');
```

- **DB_SERVER**: Dirección del servidor MySQL (127.0.0.1 o localhost)
- **DB_USER**: Usuario MySQL (generalmente 'root' en desarrollo)
- **DB_PASS**: Contraseña (vacío si no tienes contraseña)
- **DB_NAME**: Nombre de la base de datos ('alimentos')

## Ubicación de Archivos

```
ALFA-FOODTRIP/
├── conexion.php              (← Incluir en tus páginas)
├── ejemplos_conexion.php     (← Ver ejemplos)
├── perfil de usuario/
├── comidas/
└── otros/
```

## Nota Importante

⚠️ **Para Producción**: Guarda los credenciales en un archivo `.env` o en un lugar seguro fuera del alcance web.

