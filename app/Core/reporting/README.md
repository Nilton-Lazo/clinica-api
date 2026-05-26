# Sistema de reportes (Core)

Infraestructura compartida. **No incluye reportes de negocio**; cada módulo los define cuando se soliciten.

## Convención por módulo

```
app/Modules/{modulo}/
  reports/
    {Nombre}ViewData.php      # Contrato ReportViewData — qué muestra el reporte
    exports/
      {Nombre}Export.php      # Excel (Maatwebsite), opcional multi-hoja
  queries/                    # (recomendado) SQL/Eloquent del reporte
  services/                   # buildViewData() + reglas
  controllers/                # export(Request) con format=pdf|xlsx
```

```
resources/views/reports/{modulo}/{nombre}.blade.php
```

## Flujo al crear un reporte nuevo

1. **Especificación** (ticket o doc): filtros, secciones, columnas, permisos, PDF/Excel.
2. **Query/Service**: una función `buildViewData()` — única fuente de datos.
3. **ViewData**: clase readonly que implementa `ReportViewData`.
4. **Blade** (si PDF): extiende `reports.layouts.pdf`.
5. **Export** (si Excel): extiende `AbstractReportExport` o hojas sueltas.
6. **Controller**: valida filtros, autoriza, delega a `ReportExportResponse`.
7. **Ruta**: `GET .../export?format=pdf|xlsx` + mismos query params que la pantalla.
8. **Frontend**: `downloadReportFile()` con la URL y filtros.

## Clases Core

| Clase | Uso |
|-------|-----|
| `ReportFormat` | pdf, xlsx, csv |
| `ReportViewData` | Contrato del reporte |
| `ReportGenerationContext` | Fecha, usuario, clínica (tabla `clinica`) |
| `ClinicaConfigService` | Lee clínica activa con caché |
| `ReportExportResponse` | `pdf()` / `excel()` |
| `ReportFilename` | Nombre de archivo con slug + fecha |

## Vista previa HTML (desarrollo)

Con `APP_DEBUG=true` y `REPORT_PREVIEW_ENABLED=true`, el controlador del reporte puede devolver HTML en lugar de PDF para ajustar el Blade en el navegador.
