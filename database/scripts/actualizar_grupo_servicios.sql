-- =============================================================================
-- ACTUALIZAR grupo_codigo, grupo_descripcion, grupo_abrev en tarifa_servicios
-- Requiere: 1) Migración ejecutada, 2) staging.servicios_raw con datos
-- Si ya borraste staging, re-importa servicios.csv a staging.servicios_raw primero.
-- Ejecutar en pgAdmin
-- =============================================================================

WITH staging_norm AS (
  SELECT
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(r.categoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) || '.' ||
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(r.subcategoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) || '.' ||
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(r.servicio_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) AS codigo_match,
    TRIM(REPLACE(r.grupo_codigo, E'\xEF\xBB\xBF', '')) AS grupo_codigo,
    LEFT(TRIM(REPLACE(COALESCE(r.grupo_descripcion, ''), E'\xEF\xBB\xBF', '')), 255) AS grupo_descripcion,
    LEFT(TRIM(REPLACE(COALESCE(r.grupo_abrev, ''), E'\xEF\xBB\xBF', '')), 20) AS grupo_abrev
  FROM staging.servicios_raw r
),
dedup AS (
  SELECT DISTINCT ON (codigo_match) codigo_match, grupo_codigo, grupo_descripcion, grupo_abrev
  FROM staging_norm
  ORDER BY codigo_match
)
UPDATE tarifa_servicios ts
SET
  grupo_codigo = d.grupo_codigo,
  grupo_descripcion = d.grupo_descripcion,
  grupo_abrev = d.grupo_abrev
FROM dedup d
WHERE ts.tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1)
  AND ts.codigo = d.codigo_match;
