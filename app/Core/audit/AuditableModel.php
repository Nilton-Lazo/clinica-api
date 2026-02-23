<?php

namespace App\Core\audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo base para entidades de negocio que deben ser auditadas.
 * Cualquier modelo que extienda esta clase registrará automáticamente
 * create, update y delete en audit_logs (quién, qué, cuándo, dónde, old/new).
 *
 * Convención: los nuevos modelos de dominio deben extender AuditableModel
 * para que la auditoría no dependa de recordar llamar a Audit::log() en servicios.
 */
abstract class AuditableModel extends Model
{
    use Auditable;
}
