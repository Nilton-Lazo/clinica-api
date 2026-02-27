# Admisión: optimización y verificación

## Qué se hizo

### 1. Base de datos (índices)

Se añadieron índices para que los listados de Admisión respondan más rápido con muchos registros y varios usuarios:

| Tabla           | Índice                                   | Uso |
|-----------------|------------------------------------------|-----|
| `pacientes`     | `(estado, created_at)`                   | Listado de pacientes filtrado por estado y ordenado por fecha. |
| `agenda_citas`  | `(programacion_medica_id, estado)`        | Listado de citas por programación y estado (agenda médica). |

**Migración:** `2026_02_23_100000_add_admision_performance_indexes.php`

### 2. Cache de catálogos

- **paciente-form:** La respuesta de `GET /api/admision/catalogos/paciente-form` (opciones para formulario: tipo documento, sexo, etc.) se cachea **24 horas**. Es dato estático por despliegue.
- **paises y ubigeos:** Sin cache (respuesta paginada; evitar problemas de serialización del paginador). Los índices y consultas ya optimizadas mantienen buenos tiempos.

### 3. Backend ya en buen estado

- **Programación médica:** Ya usa `with()` para especialidad, médico, turno, consultorio. Sin N+1.
- **Agenda médica (opciones, listar citas, slots):** Ya usa `with()` y consultas agrupadas. Listado de citas no necesita relación `paciente` (se usa `paciente_nombre` en la tabla).
- **Pacientes:** `paginate` no carga relaciones en el listado (solo datos del modelo). `loadFull` usa `load()` con relaciones anidadas (una sola carga por detalle).
- **Catálogos:** `pacienteForm` cacheado; `paises` y `ubigeos` consultas directas con paginación.

---

## Qué debes ejecutar tú

### En la aplicación (terminal)

Ejecutar la migración para crear los índices:

```bash
cd d:\Clinica-web\clinica-api
php artisan migrate --force
```

Si prefieres no usar migración y crear los índices a mano en **pgAdmin**, usa el apartado siguiente.

### En pgAdmin (opcional: índices a mano)

Si no quieres ejecutar la migración, puedes crear los índices en pgAdmin con:

```sql
-- Índice para listado de pacientes (estado + fecha)
CREATE INDEX CONCURRENTLY IF NOT EXISTS pacientes_estado_created_at_index
ON pacientes (estado, created_at);

-- Índice para listado de citas por programación y estado
CREATE INDEX CONCURRENTLY IF NOT EXISTS agenda_citas_prog_estado_index
ON agenda_citas (programacion_medica_id, estado);
```

`CONCURRENTLY` evita bloquear la tabla; quita `CONCURRENTLY` si pgAdmin no lo acepta en tu versión.

---

## Cómo verificar que todo está bien

### 1. Índices creados

En pgAdmin:

```sql
SELECT indexname, indexdef
FROM pg_indexes
WHERE tablename IN ('pacientes', 'agenda_citas')
  AND indexname IN ('pacientes_estado_created_at_index', 'agenda_citas_prog_estado_index');
```

Deberías ver las dos filas con los índices nuevos.

### 2. Cache de paciente-form

- Primera petición: `GET /api/admision/catalogos/paciente-form` (con token) → responde con opciones.
- Segunda petición (misma sesión o otra): misma respuesta en menos tiempo si el cache está activo (driver `file` o `redis` en `CACHE_DRIVER`).

### 3. Listados más rápidos

- **Pacientes:** Listado con filtro por estado y búsqueda; debería notarse estable con muchos registros gracias al índice.
- **Agenda:** Al abrir la agenda de un día y médico, el listado de citas usa el índice por `programacion_medica_id` y `estado`.

### 4. Invalidar cache de paciente-form (si cambias enums)

Si en el futuro cambias opciones del formulario (enums) y necesitas que se vean de inmediato:

```bash
php artisan cache:forget admision.catalogos.paciente_form
```

O en código: `Cache::forget('admision.catalogos.paciente_form');`

---

## Próximos pasos posibles (no hechos en este cambio)

- **Historia clínica:** Mismo criterio: índices en tablas que filtres/ordenes (p. ej. `paciente_planes` si listas por paciente) y cache solo donde sea dato estático.
- **Citas (atención):** Revisar consultas de servicios por cita y añadir `with()` si hubiera N+1.
- **Frontend:** Debounce en búsqueda de pacientes, paginación consistente, evitar refetch innecesario (por ejemplo con React Query o estado global).
- **Componente grande:** `ServiciosSolicitadosSection.tsx` (más de 1000 líneas) se puede dividir por fases en subcomponentes o hooks sin cambiar lógica.

Todo lo anterior mantiene la lógica actual y solo mejora rendimiento y mantenibilidad.
