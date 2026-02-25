-- =============================================================================
-- Cargar tarifario base desde staging (después de importar CSV a staging)
-- Ejecutar TODO el bloque en una sola transacción en pgAdmin/psql.
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- Parte A: Limpiar tarifario base
-- -----------------------------------------------------------------------------
DELETE FROM tarifa_servicios
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

DELETE FROM tarifa_subcategorias
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

DELETE FROM tarifa_categorias
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

-- -----------------------------------------------------------------------------
-- Parte B: Categorías (deduplicado por codigo para evitar llave duplicada)
-- -----------------------------------------------------------------------------
INSERT INTO tarifa_categorias (tarifa_id, codigo, nombre, estado, created_at, updated_at)
SELECT
  (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1),
  codigo_norm,
  nombre_uno,
  'ACTIVO',
  NOW(),
  NOW()
FROM (
  SELECT
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) AS codigo_norm,
    (array_agg(TRIM(REPLACE(nombre, E'\xEF\xBB\xBF', ''))))[1] AS nombre_uno
  FROM staging.categorias_raw
  GROUP BY RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2)
) dedup;

-- -----------------------------------------------------------------------------
-- Parte C: Subcategorías
-- -----------------------------------------------------------------------------
INSERT INTO tarifa_subcategorias (tarifa_id, categoria_id, codigo, nombre, estado, created_at, updated_at)
SELECT t.id, c.id, subcod_norm, subnom_uno, 'ACTIVO', NOW(), NOW()
FROM (
  SELECT
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(s.categoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) AS cat_cod,
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(s.subcategoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) AS subcod_norm,
    (array_agg(TRIM(REPLACE(s.subcategoria_nombre, E'\xEF\xBB\xBF', ''))))[1] AS subnom_uno
  FROM staging.subcategorias_raw s
  GROUP BY
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(s.categoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2),
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(s.subcategoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2)
) agg
JOIN tarifas t ON t.tarifa_base = true
JOIN tarifa_categorias c ON c.tarifa_id = t.id AND c.codigo = agg.cat_cod;

-- -----------------------------------------------------------------------------
-- Parte D: Servicios (con grupo_codigo, grupo_descripcion, grupo_abrev)
-- -----------------------------------------------------------------------------
WITH src AS (
  SELECT
    r.*,
    t.id AS tarifa_id,
    c.id AS categoria_id,
    sc.id AS subcategoria_id,
    NULLIF(TRIM(REPLACE(COALESCE(r.nomenclador, ''), E'\xEF\xBB\xBF', '')), '') AS nom_orig
  FROM staging.servicios_raw r
  JOIN tarifas t ON t.tarifa_base = true
  JOIN tarifa_categorias c ON c.tarifa_id = t.id
    AND c.codigo = RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(r.categoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2)
  JOIN tarifa_subcategorias sc ON sc.tarifa_id = t.id AND sc.categoria_id = c.id
    AND sc.codigo = RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(r.subcategoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2)
),
with_nom AS (
  SELECT
    *,
    CASE
      WHEN nom_orig IS NULL THEN NULL
      WHEN ROW_NUMBER() OVER (PARTITION BY nom_orig ORDER BY categoria_id, subcategoria_id, descripcion) = 1 THEN nom_orig
      ELSE NULL
    END AS nomenclador_final
  FROM src
),
codigo_completo AS (
  SELECT
    *,
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(categoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) || '.' ||
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(subcategoria_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) || '.' ||
    RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(servicio_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2) AS codigo_full
  FROM with_nom
),
dedup AS (
  SELECT DISTINCT ON (tarifa_id, codigo_full)
    *
  FROM codigo_completo
  ORDER BY tarifa_id, codigo_full, descripcion
)
INSERT INTO tarifa_servicios (
  tarifa_id, categoria_id, subcategoria_id, servicio_codigo, codigo,
  nomenclador, descripcion, precio_sin_igv, unidad,
  grupo_codigo, grupo_descripcion, grupo_abrev,
  estado, created_at, updated_at
)
SELECT
  tarifa_id,
  categoria_id,
  subcategoria_id,
  RIGHT(LPAD(REGEXP_REPLACE(TRIM(REPLACE(servicio_codigo, E'\xEF\xBB\xBF', '')), '[^0-9]', '', 'g'), 2, '0'), 2),
  codigo_full,
  LEFT(nomenclador_final, 50),
  LEFT(TRIM(REPLACE(descripcion, E'\xEF\xBB\xBF', '')), 255),
  COALESCE(NULLIF(REPLACE(TRIM(REPLACE(COALESCE(precio_sin_igv, '0'), E'\xEF\xBB\xBF', '')), ',', '.'), ''), '0')::numeric(14,3),
  COALESCE(NULLIF(REPLACE(TRIM(REPLACE(COALESCE(unidad, '1'), E'\xEF\xBB\xBF', '')), ',', '.'), ''), '1')::numeric(14,3),
  NULLIF(LEFT(TRIM(REPLACE(COALESCE(grupo_codigo, ''), E'\xEF\xBB\xBF', '')), 20), ''),
  NULLIF(LEFT(TRIM(REPLACE(COALESCE(grupo_descripcion, ''), E'\xEF\xBB\xBF', '')), 255), ''),
  NULLIF(LEFT(TRIM(REPLACE(COALESCE(grupo_abrev, ''), E'\xEF\xBB\xBF', '')), 20), ''),
  'ACTIVO',
  NOW(),
  NOW()
FROM dedup;

COMMIT;

-- Verificación
SELECT
  (SELECT COUNT(*) FROM tarifa_categorias WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS categorias,
  (SELECT COUNT(*) FROM tarifa_subcategorias WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS subcategorias,
  (SELECT COUNT(*) FROM tarifa_servicios WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS servicios,
  (SELECT COUNT(*) FROM tarifa_servicios WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true) AND grupo_codigo IS NOT NULL) AS servicios_con_grupo;
