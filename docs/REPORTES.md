# Reportes — guía para el equipo

## Datos de la clínica (tabla `clinica`)

Los encabezados de reportes leen la tabla **`clinica`** (no `.env`).

| Columna | Qué poner | Ejemplo |
|---------|-----------|---------|
| `razon_social` | Nombre legal o comercial de la clínica (obligatorio) | `Clínica San José S.A.C.` |
| `ruc` | RUC con 11 dígitos | `20123456789` |
| `direccion` | Dirección fiscal o de atención | `Av. Javier Prado Este 4200, Surco, Lima` |
| `telefono` | Teléfono central | `01 618 9000` |
| `email` | Correo de contacto | `admision@clinicasanjose.com` |
| `sitio_web` | URL del sitio (con o sin https) | `https://www.clinicasanjose.com` |
| `logo_path` | Ruta del logo dentro de `public/` | `reports/logo.png` |
| `estado` | `ACTIVO` para usar en reportes; `INACTIVO` para ignorar | `ACTIVO` |

El archivo del logo debe existir en disco, por ejemplo:

`clinica-api/public/reports/logo.png`

```sql
INSERT INTO clinica (
  razon_social,
  ruc,
  direccion,
  telefono,
  email,
  sitio_web,
  logo_path,
  estado,
  created_at,
  updated_at
) VALUES (
  'Clínica San José S.A.C.',
  '20123456789',
  'Av. Javier Prado Este 4200, Santiago de Surco, Lima',
  '01 618 9000',
  'admision@clinicasanjose.com',
  'https://www.clinicasanjose.com',
  'reports/logo.png',
  'ACTIVO',
  NOW(),
  NOW()
);
```

Si antes insertaste en `instituciones`, ejecuta `php artisan migrate` (renombra a `clinica`) o mueve los datos manualmente.

Tras editar la tabla: `php artisan cache:clear` o espera el TTL (`REPORT_INSTITUTION_CACHE_TTL`, default 300 s).

## Cómo probar el reporte de filiación

### Forma correcta (producción)

1. Fila activa en `clinica`.
2. Iniciar sesión en el portal.
3. Historia clínica → abrir paciente guardado → **Hoja de filiación**.

Eso descarga el PDF con token Sanctum automáticamente.

### Pegar URL en el navegador

```
http://192.168.1.35:8000/api/admision/pacientes/5/reporte-filiacion?format=pdf
```

Sin iniciar sesión verás **401 No autorizado** (correcto). Ya no debe aparecer error 500.

El navegador no envía el token del portal; por eso no sirve como método habitual de prueba.

### curl con token

```powershell
curl -H "Authorization: Bearer TU_TOKEN" "http://192.168.1.35:8000/api/admision/pacientes/5/reporte-filiacion?format=pdf" -o hoja.pdf
```

## Vista previa HTML (`REPORT_PREVIEW_ENABLED`)

Solo para **desarrollo**: ver el diseño del PDF como página HTML antes de ajustar el Blade.

- No la usan los usuarios finales.
- Requiere `APP_DEBUG=true`, `REPORT_PREVIEW_ENABLED=true` y token en la petición.
- En producción dejar `REPORT_PREVIEW_ENABLED=false`.

El funcionamiento real del módulo es el **PDF descargado** (botón en la app o API con token).

## Diseño del documento

| Archivo |
|---------|
| `resources/views/reports/admision/hoja-filiacion-paciente.blade.php` |
| `resources/views/reports/partials/institution-header.blade.php` |
| `resources/views/reports/layouts/pdf.blade.php` |
