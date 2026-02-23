# Verificación: reglas Particular y Privado (precio directo)

## 1. Base de datos

- La tabla `tarifas` debe tener la columna **`es_precio_directo`** (boolean).
- Para **Particular** y **Privado** debe ser `true`; para el resto (Tarifario base, Pacifico, etc.) suele ser `false`.

Si acabas de recrear las tarifas después de las migraciones, ejecuta:

```bash
php artisan migrate
```

Existe una migración que asigna `es_precio_directo = true` a las filas con `descripcion_tarifa` igual a "Particular" o "Privado" (sin importar mayúsculas/espacios).

Comprobar en BD:

```sql
SELECT id, codigo, descripcion_tarifa, es_precio_directo FROM tarifas;
```

Particular (002) y Privado (003) deben tener `es_precio_directo = true`.

---

## 2. Cómo se usa en la app

- El frontend recibe **`tarifa_es_precio_directo`** en cada **plan** del paciente (desde la tarifa del tipo de cliente).
- Si el plan seleccionado tiene `tarifa_es_precio_directo === true` (Particular/Privado):
  - No se muestra la sección **"Definir Copago variable"**.
  - No se muestran columnas de copago variable/fijo en la tabla de servicios.
  - No se muestra la sección **"Detalle para reporte"** (tabla resumen con copagos, pago aseguradora, etc.).
  - SOAT se deshabilita (no aplica).
- Para otros tarifarios (Pacifico, etc.) esas secciones y columnas sí se muestran.

---

## 3. Checklist de verificación en la pantalla Atención de cita

1. **Plan Particular o Privado**
   - Entra a una cita con un paciente que tenga un plan cuyo tipo de cliente use tarifa **Particular** o **Privado**.
   - En "Datos del paciente", el combo debe mostrar ese plan (ej. "002 / Particular").
   - **Comprueba:** no debe verse "Definir Copago variable" junto a "Servicios finales".
   - **Comprueba:** en la tabla de servicios no deben aparecer columnas "Copago variable" ni "Copago fijo".
   - **Comprueba:** al final, **no** debe aparecer el bloque **"Detalle para reporte"** (tabla con Código, Descripción, Cantidad, Precio unitario, Copago variable, etc.).

2. **Plan con otro tarifario (ej. Pacifico)**
   - Elige un paciente con plan que use tarifa **Pacifico** (u otra que no sea Particular/Privado).
   - **Comprueba:** sí debe verse "Definir Copago variable" y las columnas de copago en la tabla.
   - **Comprueba:** sí debe aparecer la sección **"Detalle para reporte"** debajo de los servicios.

3. **Cambiar de plan en la misma pantalla**
   - Con la cita abierta, cambia el plan de "Particular" a "Pacifico" (o al revés).
   - **Comprueba:** "Definir Copago variable", columnas de copago y "Detalle para reporte" aparecen o desaparecen según el plan seleccionado.

---

## 4. Si algo no cuadra

- Revisa que las migraciones estén ejecutadas: `php artisan migrate:status`.
- Confirma en BD: `SELECT id, codigo, descripcion_tarifa, es_precio_directo FROM tarifas;`
- En el frontend, en la pestaña Red (DevTools), al cargar la atención de la cita, revisa la respuesta del API: en `planes[]` cada ítem debe traer `tarifa_es_precio_directo` (true para Particular/Privado, false para el resto).
