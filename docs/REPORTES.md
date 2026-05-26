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
3. Historia clínica → abrir paciente guardado → icono **Imprimir** (hoja de filiación).

Se abre la vista previa HTML; desde ahí el usuario puede **Imprimir** o, si lo desea, **Descargar PDF**.

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

## Vista previa e impresión

La hoja de filiación usa la plantilla **`reports/layouts/pdf`** (diseño oficial con borde, pie fijo y márgenes A4). La misma plantilla alimenta:

1. **Vista previa HTML** en el portal (`?preview=1`) — el usuario imprime desde el navegador.
2. **PDF** generado con **DomPDF** por defecto (mismo motor que el archivo de referencia). Opcional: `REPORT_PDF_DRIVER=browsershot` si Node/Puppeteer están instalados.

### Variables de entorno (API)

| Variable | Default | Uso |
|----------|---------|-----|
| `REPORT_PREVIEW_ENABLED` | `true` | HTML de vista previa en el portal |
| `REPORT_PDF_DRIVER` | `browsershot` | `browsershot` o `dompdf` (respaldo) |
| `BROWSERSHOT_NO_SANDBOX` | `true` | Linux/Docker: `--no-sandbox` |
| `BROWSERSHOT_NODE_BINARY` | — | Ruta a `node` si no está en PATH |
| `BROWSERSHOT_NPM_BINARY` | — | Ruta a `npm` |
| `BROWSERSHOT_CHROME_PATH` | — | Ruta a Chrome/Chromium |

### Requisitos Browsershot (servidor)

```bash
cd clinica-api
npm install puppeteer
```

Node.js debe estar instalado. El logo de la clínica debe resolverse por URL pública (`APP_URL` + `storage/...`) para que Chromium lo cargue al generar el PDF.

## Diseño del documento

| Archivo |
|---------|
| `resources/views/reports/admision/hoja-filiacion-paciente.blade.php` |
| `resources/views/reports/partials/institution-header.blade.php` |
| `resources/views/reports/layouts/pdf.blade.php` |
