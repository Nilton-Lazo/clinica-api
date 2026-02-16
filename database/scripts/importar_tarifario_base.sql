-- =============================================================================
-- IMPORTAR TARIFARIO BASE DESDE CSV (categorias, subcategorias, servicios)
-- Ejecutar en pgAdmin contra tu base de datos PostgreSQL
-- =============================================================================
-- PASO 0: Verificar que exista el tarifario base
-- =============================================================================
-- Ejecuta esto primero. Si no devuelve filas, crea una tarifa con tarifa_base = true.
SELECT id, codigo, descripcion_tarifa
FROM tarifas
WHERE tarifa_base = true;

-- Si no existe, créala:
-- INSERT INTO tarifas (codigo, descripcion_tarifa, tarifa_base, estado, created_at, updated_at)
-- VALUES ('001', 'Tarifario Base', true, 'ACTIVO', NOW(), NOW());

-- =============================================================================
-- PASO 1: Crear / recrear tablas staging
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS staging;

DROP TABLE IF EXISTS staging.servicios_raw;
DROP TABLE IF EXISTS staging.subcategorias_raw;
DROP TABLE IF EXISTS staging.categorias_raw;

-- Estructura según tus nuevos CSV (columnas deben coincidir con los encabezados)
CREATE TABLE staging.categorias_raw (
  codigo text NOT NULL,
  nombre text NOT NULL
);

CREATE TABLE staging.subcategorias_raw (
  categoria_codigo text NOT NULL,
  subcategoria_codigo text NOT NULL,
  subcategoria_nombre text NOT NULL
);

CREATE TABLE staging.servicios_raw (
  categoria_codigo text NOT NULL,
  subcategoria_codigo text NOT NULL,
  servicio_codigo text NOT NULL,
  descripcion text NOT NULL,
  unidad text,
  precio_sin_igv text,
  nomenclador text,
  grupo_codigo text,
  grupo_descripcion text,
  grupo_abrev text
);

-- =============================================================================
-- PASO 2: Importar los CSV en pgAdmin
-- =============================================================================
-- En pgAdmin:
-- 1. Clic derecho en staging.categorias_raw → Import/Export
-- 2. Import, selecciona categorias.csv, Encoding UTF-8, Delimiter coma (o ; si usas punto y coma)
-- 3. Header: Yes
-- 4. Repite para subcategorias_raw (subcategorias.csv) y servicios_raw (servicios.csv)

-- O desde psql / línea de comandos:
-- \copy staging.categorias_raw (codigo, nombre) FROM 'ruta/categorias.csv' WITH (FORMAT csv, HEADER true, ENCODING 'UTF8');
-- \copy staging.subcategorias_raw (categoria_codigo, subcategoria_codigo, subcategoria_nombre) FROM 'ruta/subcategorias.csv' WITH (FORMAT csv, HEADER true, ENCODING 'UTF8');
-- \copy staging.servicios_raw (categoria_codigo, subcategoria_codigo, servicio_codigo, descripcion, unidad, precio_sin_igv, nomenclador, grupo_codigo, grupo_descripcion, grupo_abrev) FROM 'ruta/servicios.csv' WITH (FORMAT csv, HEADER true, ENCODING 'UTF8');

-- =============================================================================
-- PASO 3: Verificar datos en staging
-- =============================================================================

SELECT 'categorias' AS tabla, COUNT(*) AS filas FROM staging.categorias_raw
UNION ALL
SELECT 'subcategorias', COUNT(*) FROM staging.subcategorias_raw
UNION ALL
SELECT 'servicios', COUNT(*) FROM staging.servicios_raw;

-- =============================================================================
-- PASO 4: LIMPIAR y CARGAR en tablas finales
-- =============================================================================
-- ATENCIÓN: Esto BORRA todo el contenido del tarifario base y lo reemplaza.
-- Ejecutar todo el bloque en una sola transacción.

BEGIN;

-- Obtener id del tarifario base
DO $$
DECLARE
  v_tarifa_id bigint;
BEGIN
  SELECT id INTO v_tarifa_id FROM tarifas WHERE tarifa_base = true LIMIT 1;
  IF v_tarifa_id IS NULL THEN
    RAISE EXCEPTION 'No existe tarifario base. Crea una tarifa con tarifa_base = true.';
  END IF;
END $$;

-- 4.1 Eliminar datos existentes (orden por FKs: servicios → subcategorias → categorias)
DELETE FROM tarifa_servicios
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

DELETE FROM tarifa_subcategorias
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

DELETE FROM tarifa_categorias
WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1);

-- 4.2 Insertar CATEGORÍAS
INSERT INTO tarifa_categorias (tarifa_id, codigo, nombre, estado, created_at, updated_at)
SELECT
  (SELECT id FROM tarifas WHERE tarifa_base = true LIMIT 1),
  RIGHT(LPAD(TRIM(codigo), 2, '0'), 2),
  TRIM(nombre),
  'ACTIVO',
  NOW(),
  NOW()
FROM staging.categorias_raw
ON CONFLICT (tarifa_id, codigo) DO UPDATE SET
  nombre = EXCLUDED.nombre,
  estado = 'ACTIVO',
  updated_at = NOW();

-- 4.3 Insertar SUBCATEGORÍAS
LOCK TABLE tarifa_subcategorias IN EXCLUSIVE MODE;

INSERT INTO tarifa_subcategorias (tarifa_id, categoria_id, codigo, nombre, estado, created_at, updated_at)
SELECT
  t.id,
  c.id,
  RIGHT(LPAD(TRIM(s.subcategoria_codigo), 2, '0'), 2),
  TRIM(s.subcategoria_nombre),
  'ACTIVO',
  NOW(),
  NOW()
FROM staging.subcategorias_raw s
JOIN tarifas t ON t.tarifa_base = true
JOIN tarifa_categorias c ON c.tarifa_id = t.id
  AND c.codigo = RIGHT(LPAD(TRIM(s.categoria_codigo), 2, '0'), 2)
GROUP BY t.id, c.id, RIGHT(LPAD(TRIM(s.subcategoria_codigo), 2, '0'), 2), TRIM(s.subcategoria_nombre)
ON CONFLICT (categoria_id, codigo) DO UPDATE SET
  nombre = EXCLUDED.nombre,
  estado = 'ACTIVO',
  updated_at = NOW();

-- 4.4 Insertar SERVICIOS (nomenclador: solo el primero por valor para evitar duplicados)
LOCK TABLE tarifa_servicios IN EXCLUSIVE MODE;

WITH src AS (
  SELECT
    r.*,
    t.id AS tarifa_id,
    c.id AS categoria_id,
    sc.id AS subcategoria_id,
    NULLIF(TRIM(NULLIF(r.nomenclador, '')), '') AS nom_orig
  FROM staging.servicios_raw r
  JOIN tarifas t ON t.tarifa_base = true
  JOIN tarifa_categorias c ON c.tarifa_id = t.id
    AND c.codigo = RIGHT(LPAD(TRIM(r.categoria_codigo), 2, '0'), 2)
  JOIN tarifa_subcategorias sc ON sc.tarifa_id = t.id
    AND sc.categoria_id = c.id
    AND sc.codigo = RIGHT(LPAD(TRIM(r.subcategoria_codigo), 2, '0'), 2)
),
with_nom AS (
  SELECT
    *,
    CASE
      WHEN nom_orig IS NULL THEN NULL
      WHEN ROW_NUMBER() OVER (
            PARTITION BY nom_orig
            ORDER BY categoria_id, subcategoria_id, descripcion
          ) = 1
        THEN nom_orig
      ELSE NULL
    END AS nomenclador_final
  FROM src
)
INSERT INTO tarifa_servicios
  (tarifa_id, categoria_id, subcategoria_id, servicio_codigo, codigo, nomenclador, descripcion, precio_sin_igv, unidad, estado, created_at, updated_at)
SELECT
  tarifa_id,
  categoria_id,
  subcategoria_id,
  RIGHT(LPAD(TRIM(servicio_codigo), 2, '0'), 2),
  RIGHT(LPAD(TRIM(categoria_codigo), 2, '0'), 2) || '.' ||
  RIGHT(LPAD(TRIM(subcategoria_codigo), 2, '0'), 2) || '.' ||
  RIGHT(LPAD(TRIM(servicio_codigo), 2, '0'), 2),
  LEFT(nomenclador_final, 50),
  LEFT(TRIM(descripcion), 255),
  COALESCE(NULLIF(REPLACE(TRIM(COALESCE(precio_sin_igv, '0')), ',', '.'), ''), '0')::numeric(14,3),
  COALESCE(NULLIF(REPLACE(TRIM(COALESCE(unidad, '1')), ',', '.'), ''), '1')::numeric(14,3),
  'ACTIVO',
  NOW(),
  NOW()
FROM with_nom;

COMMIT;

-- =============================================================================
-- PASO 5: Verificar resultado
-- =============================================================================

SELECT
  (SELECT COUNT(*) FROM tarifa_categorias WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS categorias,
  (SELECT COUNT(*) FROM tarifa_subcategorias WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS subcategorias,
  (SELECT COUNT(*) FROM tarifa_servicios WHERE tarifa_id = (SELECT id FROM tarifas WHERE tarifa_base = true)) AS servicios;
