# Orden para que el tarifario funcione como antes

## 1. Migraciones y datos base

```bash
php artisan migrate
php artisan db:seed
```

Con el seed se llena la tabla **`grupos_servicio`** (GruposServicioSeeder). Esa tabla es la que usa Facturación → Tarifario para mostrar **todos** los grupos en el campo Grupo y en el filtro. Si no ejecutas el seed, solo verás los grupos que aparezcan en los servicios cargados.

## 2. Tarifario base

Si no tienes tarifa con `tarifa_base = true`, créala (una sola vez). En pgAdmin o psql:

```sql
SELECT id, codigo, descripcion_tarifa FROM tarifas WHERE tarifa_base = true;
-- Si no hay filas:
-- INSERT INTO tarifas (codigo, descripcion_tarifa, tarifa_base, requiere_acreditacion, estado, created_at, updated_at)
-- VALUES ('001', 'Tarifario Base', true, false, 'ACTIVO', NOW(), NOW());
```

## 3. Staging e importar CSV

En **pgAdmin** (o script SQL):

1. Crear schema y tablas staging: ejecuta el **PASO 1** de `importar_tarifario_base.sql` (CREATE SCHEMA, DROP TABLE, CREATE TABLE para categorias_raw, subcategorias_raw, servicios_raw).
2. Importar CSV:
   - `categorias_raw` ← categorias.csv (columnas: codigo, nombre)
   - `subcategorias_raw` ← subcategorias.csv (categoria_codigo, subcategoria_codigo, subcategoria_nombre)
   - `servicios_raw` ← servicios.csv (categoria_codigo, subcategoria_codigo, servicio_codigo, descripcion, unidad, precio_sin_igv, nomenclador, grupo_codigo, grupo_descripcion, grupo_abrev)

## 4. Cargar a tablas finales

**No uses** el script suelto que empieza con `ROLLBACK;` (no hay transacción y además el INSERT de servicios no incluye grupo). Usa:

**`cargar_tarifario_desde_staging.sql`**

- Ejecuta **todo el archivo de una vez** (BEGIN … COMMIT).
- Hace DELETE del tarifario base, INSERT de categorías (con deduplicación), subcategorías y servicios **con** grupo_codigo, grupo_descripcion, grupo_abrev.

Después de esto, en la app deberías ver todos los grupos (desde `grupos_servicio`) y en Grupo/filtro solo la **descripción** (ej. "Clinica"), no el código ni la abreviatura.

## Resumen

| Paso | Acción |
|------|--------|
| 1 | `php artisan migrate` y `php artisan db:seed` (para tener `grupos_servicio` y el resto de catálogos) |
| 2 | Asegurar que exista una tarifa con `tarifa_base = true` |
| 3 | Crear tablas staging e importar los 3 CSV |
| 4 | Ejecutar **todo** `cargar_tarifario_desde_staging.sql` en una sola transacción |
