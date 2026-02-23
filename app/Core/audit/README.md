# Auditoría

## Cimiento del proyecto: auditoría automática

Toda entidad de negocio que se cree, actualice o elimine queda registrada en `audit_logs` **de forma automática**, sin depender de que el desarrollador recuerde llamar a `Audit::log()` en cada servicio.

### Cómo funciona

- **Modelos de dominio:** deben extender `App\Core\audit\AuditableModel` (no `Illuminate\Database\Eloquent\Model`).
- **User:** extiende `Authenticatable` y usa el trait `App\Core\audit\Auditable`.
- **AuditableModel** usa el trait **Auditable**, que en los eventos Eloquent `creating`, `updating` y `deleting` registra en `audit_logs`:
  - Quién (actor_id, actor_username)
  - Qué (action, entity_type, entity_id, entity_display_name)
  - Cómo (old_values, new_values en JSON)
  - Cuándo (created_at)
  - Dónde (module, route, ip_address, user_agent)

### Convención obligatoria

**Todo nuevo modelo que represente datos de negocio que deban auditarse debe extender `AuditableModel`.**

Ejemplo:

```php
use App\Core\audit\AuditableModel;

class MiEntidad extends AuditableModel
{
    // ...
}
```

Así no se puede "olvidar" auditar: si el modelo extiende `AuditableModel`, queda auditado por defecto.

### Excepciones

- **AuditLog** no debe ser auditable (evita recursión). Extiende `Model` directamente.
- Modelos que sean solo catálogos de referencia y no requieran trazabilidad pueden seguir extendiendo `Model` si el equipo lo decide; por defecto, se recomienda usar `AuditableModel`.

### Nombre legible en la auditoría

Para que en el visor de auditorías aparezca un texto tipo "Modificó el paciente Juan Pérez", el modelo puede implementar:

```php
public function resolveAuditableDisplayName(): string
{
    return $this->nombre_completo ?? ('Paciente #' . $this->getKey());
}
```

### Campos sensibles

Por defecto no se guardan en `old_values`/`new_values`: `password`, `remember_token`.  
Para excluir más atributos en un modelo:

```php
protected array $auditExclude = ['password', 'remember_token', 'campo_secreto'];
```

### Acciones que no son cambio de modelo

Login, logout, intentos fallidos, etc. se siguen registrando con `Audit::log()` en el servicio o middleware correspondiente (LoginService, AuditMiddleware, etc.).
