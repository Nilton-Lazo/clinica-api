<?php

namespace App\Core\notifications;

use App\Core\audit\AuditContext;
use App\Core\notifications\Models\UserNotification;

class NotificationService
{

    private const ENTITY_LABELS = [
        'especialidad'         => 'Especialidad',
        'consultorio'          => 'Consultorio',
        'medico'               => 'Médico',
        'turno'                => 'Turno',
        'iafa'                 => 'IAFA',
        'tipo_iafa'            => 'Tipo de IAFA',
        'tipo_cliente'         => 'Tipo de cliente',
        'contratante'          => 'Contratante',
        'tarifa'               => 'Tarifa',
        'tarifa_categoria'     => 'Categoría',
        'tarifa_subcategoria'  => 'Subcategoría',
        'tarifa_servicio'      => 'Servicio',
        'tarifa_recargo_noche' => 'Recargo nocturno',
        'parametro_sistema'    => 'Parámetro',
        'paciente'             => 'Paciente',
        'plan_paciente'        => 'Plan del paciente',
        'agenda_cita'          => 'Cita',
        'cita_atencion'        => 'Atención',
        'programacion_medica'  => 'Programación médica',
    ];

    private const ACTION_CONFIG = [
        'create'  => ['type' => 'success', 'verb' => 'creado/a',      'past' => 'Creado/a'],
        'update'  => ['type' => 'success', 'verb' => 'actualizado/a', 'past' => 'Actualizado/a'],
        'delete'  => ['type' => 'info',    'verb' => 'eliminado/a',   'past' => 'Eliminado/a'],
        'disable' => ['type' => 'warning', 'verb' => 'desactivado/a', 'past' => 'Desactivado/a'],
        'enable'  => ['type' => 'success', 'verb' => 'reactivado/a',  'past' => 'Reactivado/a'],
        'archive' => ['type' => 'info',    'verb' => 'archivado/a',   'past' => 'Archivado/a'],
        'restore' => ['type' => 'success', 'verb' => 'restaurado/a',  'past' => 'Restaurado/a'],
        'clone'   => ['type' => 'success', 'verb' => 'clonado/a',     'past' => 'Clonado/a'],
    ];

    public function __construct(private AuditContext $context) {}

    public function notifyAction(
        string $entityType,
        string $actionType,
        ?int $entityId = null,
        ?string $entityName = null,
        array $metadata = [],
        ?int $userId = null,
    ): UserNotification {
        $resolvedUserId = $userId ?? $this->resolveUserId();

        $config      = self::ACTION_CONFIG[$actionType] ?? ['type' => 'info', 'verb' => $actionType, 'past' => $actionType];
        $entityLabel = self::ENTITY_LABELS[$entityType] ?? ucfirst(str_replace('_', ' ', $entityType));

        $title   = $this->buildTitle($entityLabel, $config['past']);
        $message = $this->buildMessage($entityLabel, $config['verb'], $entityName);

        return UserNotification::create([
            'user_id'     => $resolvedUserId,
            'type'        => $config['type'],
            'entity_type' => $entityType,
            'action_type' => $actionType,
            'entity_id'   => $entityId,
            'entity_name' => $entityName,
            'title'       => $title,
            'message'     => $message,
            'metadata'    => $metadata ?: null,
        ]);
    }

    public function notifyError(
        string $title,
        string $message,
        ?int $userId = null,
        array $metadata = [],
    ): UserNotification {
        return UserNotification::create([
            'user_id'  => $userId ?? $this->resolveUserId(),
            'type'     => 'error',
            'title'    => $title,
            'message'  => $message,
            'metadata' => $metadata ?: null,
        ]);
    }


    private function resolveUserId(): ?int
    {
        $actor = $this->context->actor();
        return isset($actor['id']) ? (int) $actor['id'] : null;
    }

    private function buildTitle(string $entityLabel, string $past): string
    {
        return "{$entityLabel} {$past}";
    }

    private function buildMessage(string $entityLabel, string $verb, ?string $entityName): string
    {
        if ($entityName) {
            return "\"{$entityName}\" {$verb} correctamente.";
        }

        return "{$entityLabel} {$verb} correctamente.";
    }
}
