# Verificación: Login y Cierre de sesión

## Cambios realizados

### Backend
- **Validación:** `identifier` y `password` con máximo 255 caracteres; `identifier` se recorta con trim.
- **Rendimiento:** La consulta de login solo selecciona las columnas necesarias (id, username, nombres, apellidos, nivel, estado, password y el campo por el que se busca).
- **Configuración:** Límites de sesión (`SESSION_IDLE_MINUTES`, `SESSION_MAX_HOURS`) se leen desde `config/session_limits.php` (cacheable en producción).
- **Logout:** Se borra la clave de caché `session:last_activity:{token_id}` al cerrar sesión para no dejar entradas huérfanas.
- **Cabeceras:** Las respuestas de login, logout y `me` envían `Cache-Control: no-store` y `Pragma: no-cache`.

### Frontend
- **Doble envío:** Si ya hay una petición en curso (`loading`), el envío del formulario se ignora.
- **Error:** Tras un login fallido se limpia el campo contraseña.
- **Mensaje:** Texto de error unificado: "Credenciales incorrectas."

### Base de datos
- **No hay cambios obligatorios.** La tabla `users` ya tiene índices únicos en `email` y `username` (constraint UNIQUE en PostgreSQL), por lo que la búsqueda por usuario o correo ya es eficiente.

---

## Cómo verificar en pgAdmin

No es necesario ejecutar ninguna sentencia para el login. Si quieres comprobar que los índices existen:

```sql
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename = 'users';
```

Deberías ver índices para `users_email_unique` y `users_username_unique` (o nombres análogos).

---

## Cómo verificar que todo funciona

### 1. Login correcto
1. Abre la app en el navegador y ve a la pantalla de login.
2. Ingresa usuario y contraseña válidos.
3. Debe redirigir a `/inicio` y la barra/sidebar debe mostrar sesión iniciada.
4. En pgAdmin, en `audit_logs`, debe aparecer una fila con `action = 'auth.login.success'` y tu `actor_username`.

### 2. Login incorrecto
1. Usuario o contraseña erróneos.
2. Debe mostrarse "Credenciales incorrectas." y el campo contraseña debe quedar vacío.
3. En `audit_logs` debe aparecer `action = 'auth.login.failed'`.

### 4. Usuario inactivo
1. En la BD, pon un usuario con `estado != 'activo'`.
2. Intenta iniciar sesión con ese usuario.
3. Debe mostrarse "Usuario inactivo." (o el mensaje que devuelva el backend).
4. En `audit_logs`, `action = 'auth.login.blocked'`.

### 5. Throttle (límite de intentos)
1. Haz muchos intentos de login fallidos en poco tiempo (por ejemplo 6 en menos de un minuto desde la misma IP).
2. El backend debe responder 429 (Too Many Requests) al superar el límite configurado para `login` (p. ej. 10/min por IP, 5/min por IP+identifier).

### 6. Logout
1. Con sesión iniciada, cierra sesión desde la app.
2. Debe redirigir al login y no permitir volver a rutas protegidas sin autenticarse.
3. En `audit_logs`, una fila con `action = 'auth.logout'`.
4. En la tabla de tokens de Sanctum (`personal_access_tokens`), el token actual de ese usuario debe haber sido eliminado.

### 7. Respuesta rápida
- Login y logout deben responder en menos de 1–2 segundos en entorno local (sin latencia de red alta). Si tienes herramientas de red (pestaña Network en DevTools), revisa el tiempo de la petición a `POST .../api/login` o `POST .../api/logout`.

---

## Variables de entorno

En `.env` (o en el servidor) puedes definir:

- `SESSION_IDLE_MINUTES`: minutos de inactividad para expirar la sesión (por defecto 15).
- `SESSION_MAX_HOURS`: vida máxima del token en horas (por defecto 8).

Tras cambiar estas variables, en producción ejecuta `php artisan config:cache` si usas caché de configuración.
