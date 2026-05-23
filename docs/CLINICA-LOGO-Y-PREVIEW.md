# Logo de la clínica y vista previa de reportes

## Puertos del proyecto

| Servicio | URL local | URL red (ejemplo) |
|----------|-----------|-------------------|
| Frontend (Vite) | http://localhost:5173 | http://192.168.1.35:5173 |
| Backend (API) | http://127.0.0.1:8000 | http://192.168.1.35:8000 |

El frontend proxya `/api` al backend (`vite.config.ts` puerto 5173 → 8000).

## Logo — forma profesional (backend)

El logo **no va en el frontend**. Es un archivo del servidor referenciado desde la tabla `clinica`.

### Ubicación en disco (recomendada)

```
clinica-api/storage/app/public/clinica/logo.png
```

(o `logo.jpg`, `logo.webp`)

### Enlace público (una vez por máquina)

```powershell
cd clinica-api
php artisan storage:link
```

Queda accesible en:

```
http://192.168.1.35:8000/storage/clinica/logo.png
```

### Valor en la tabla `clinica.logo_path`

Guarda la ruta **relativa al disco public** de Laravel:

```
clinica/logo.png
```

No uses rutas absolutas de Windows (`D:\...`). No subas el logo al repo de frontend.

### Ejemplo SQL completo

```sql
UPDATE clinica
SET
  razon_social = 'Mi Clínica S.A.C.',
  ruc = '20123456789',
  direccion = 'Av. Principal 123, Lima',
  telefono = '01 200 0000',
  email = 'contacto@miclinica.com',
  sitio_web = 'https://www.miclinica.com',
  logo_path = 'clinica/logo.png',
  estado = 'ACTIVO',
  updated_at = NOW()
WHERE id = 1;
```

Pasos:

1. Copiar imagen a `storage/app/public/clinica/logo.png`
2. `php artisan storage:link` (si no existe `public/storage`)
3. Actualizar `logo_path` en BD
4. `php artisan cache:clear`

## Rendimiento del PDF en desarrollo

`php artisan serve` atiende **una petición a la vez**. Mientras genera el PDF, el resto del API (menú, guardar, navegar) queda en espera.

Optimizaciones aplicadas: carga mínima del paciente, caché de datos de clínica, logo por ruta de archivo (no base64), DomPDF sin recursos remotos.

Para desarrollo con mejor concurrencia usa Laragon/XAMPP, `php -S` con proxy, o en producción PHP-FPM con varios workers. Logo recomendado: PNG/JPG **menor a 300 KB**, máximo ~800 px de ancho.

## Vista previa del reporte

### Por qué pegar la URL en el navegador no funciona

Toda la app exige **sesión** (token Sanctum en `sessionStorage`, clave `erp:auth:token`). Una pestaña nueva sin ese token recibe **401**.

La vista previa **real para desarrollo** es el botón **Vista previa** en la ficha del paciente (Historia clínica → paciente guardado). Ese botón envía el token automáticamente.

### Activar vista previa HTML (solo desarrollo)

En `clinica-api/.env`:

```env
APP_DEBUG=true
REPORT_PREVIEW_ENABLED=true
```

Reinicia el servidor API (`php artisan serve`) tras cambiar `.env`.

La vista previa usa `?format=pdf&preview=1`. **Descargar PDF** no envía `preview` y siempre recibe un PDF real.

### Probar desde la app

1. Inicia sesión en http://localhost:5173 (o tu IP :5173)
2. Abre un paciente en Historia clínica
3. Clic en **Vista previa** → nueva pestaña con el HTML del reporte
4. **Descargar PDF** → archivo PDF con la misma data

Si `REPORT_PREVIEW_ENABLED=false`, **Vista previa** abrirá el PDF en la pestaña (sigue siendo válido).

### Probar URL manual (avanzado, con token)

1. Inicia sesión en el portal
2. F12 → Application → Session Storage → `erp:auth:token` → copia el valor
3. Extensión del navegador "ModHeader" o similar: header `Authorization: Bearer TOKEN`
4. Abre:

```
http://localhost:5173/api/admision/pacientes/ID/reporte-filiacion?format=pdf&preview=1
```

Usa el proxy del frontend (`5173/api`), no el puerto 8000 directo, si quieres mismo origen que la app.

O con curl:

```powershell
curl.exe -H "Authorization: Bearer TU_TOKEN" "http://192.168.1.35:8000/api/admision/pacientes/5/reporte-filiacion?format=pdf" -o preview.html
```

Si preview está activo, el archivo será HTML; ábrelo con el navegador.
