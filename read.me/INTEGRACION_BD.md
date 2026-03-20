# PHP Perfil de Usuario - Integración con Base de Datos

## Archivos Creados

### 1. **perfil_publico.php**
Versión PHP del perfil que carga datos de la base de datos.

**Características:**
- Obtiene datos del usuario desde la BD (nombre, email, rol)
- Carga recetas del usuario
- Calcula rating promedio basado en comentarios
- Permite editar el perfil (apenas es el usuario propio)
- Muestra si es perfil propio o visitante
- Vincula recetas a una página de detalle

**Uso:**
```
/perfil de usuario/perfil_publico/perfil_publico.php?id=4
```

### 2. **actualizar_perfil.php**
Procesa las actualizaciones del perfil mediante AJAX.

**Recibe:**
- `id` (ID del usuario)
- `nombre` (Nuevo nombre)
- `email` (Nuevo email)

**Retorna JSON:**
```json
{ "success": true, "message": "Perfil actualizado correctamente" }
```

## Cómo Funciona la Integración

### Flujo de Datos:

```
1. Usuario visita: perfil_publico.php?id=4
   ↓
2. PHP carga datos de la BD:
   - SELECT usuarios WHERE id = 4
   - SELECT recetas WHERE usuario_id = 4
   - SELECT AVG(puntuacion) FROM comentarios...
   ↓
3. HTML se renderiza con datos reales
   ↓
4. Si el usuario hace clic en "Editar":
   - Se abre panel de edición
   - Se envía AJAX a actualizar_perfil.php
   ↓
5. actualizar_perfil.php:
   - Valida datos
   - Actualiza en BD
   - Retorna JSON con resultado
   ↓
6. JavaScript muestra confirmación
```

## Requisitos

✅ `conexion.php` en la raíz del proyecto
✅ Base de datos `alimentos` con tabla `usuarios`, `recetas`, `comentarios`
✅ Servidor PHP habilitado
✅ Base de datos MariaDB/MySQL corriendo

## Ejemplo de URL para Acceder

```
http://localhost/ALFA-FOODTRIP/perfil de usuario/perfil_publico/perfil_publico.php?id=4
```

Para el usuario "Angel" (ID = 4) en la base de datos.

## Estructura de Datos Esperada

### Tabla: usuarios
```sql
- id (INT, PK)
- nombre (VARCHAR)
- email (VARCHAR)
- password (VARCHAR)
- rol (ENUM: usuario, restaurante, admin)
```

### Tabla: recetas
```sql
- id (INT, PK)
- usuario_id (INT, FK)
- titulo (VARCHAR)
- descripcion (TEXT)
- fecha_publicacion (TIMESTAMP)
```

### Tabla: imagenes
```sql
- id (INT, PK)
- receta_id (INT, FK)
- ruta (VARCHAR)
- orden (INT)
```

### Tabla: comentarios
```sql
- id (INT, PK)
- receta_id (INT, FK)
- usuario_id (INT, FK)
- puntuacion (TINYINT 0-5)
```

## Funciones PHP Utilizadas

Desde `conexion.php`:

```php
// Obtener datos
$resultado = obtenerDatos($sql, "i", [4]);
$fila = $resultado->fetch_assoc();

// Actualizar datos
ejecutarConsulta($sql, "ssi", ['nombre', 'email', 4]);

// Cerrar conexión
cerrarConexion();
```

## Seguridad

✅ **Prepared Statements** - Protege contra SQL Injection
✅ **htmlspecialchars()** - Evita XSS en salida
✅ **Validación de Email** - filter_var()
✅ **Validación de ID** - intval()
✅ **Content-Type: application/json** - Previene XSS en AJAX

## Próximos Pasos (Opcionales)

1. Agregar autenticación con sesiones
2. Agregar foto de perfil (subida de archivos)
3. Sistema de "Seguir" usuarios
4. Mostrar comentarios en recetas
5. Agregar búsqueda de usuarios

