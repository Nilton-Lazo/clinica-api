<?php

namespace App\Core\realtime;

use Illuminate\Database\Eloquent\Model;

class RealtimeModelObserver
{
    public function __construct(
        private RealtimeBroadcaster $realtime,
    ) {}

    public function created(Model $model): void
    {
        $this->emit($model, 'created');
    }

    public function updated(Model $model): void
    {
        $action = $this->isDisabled($model) ? 'disabled' : 'updated';
        $this->emit($model, $action);
    }

    public function deleted(Model $model): void
    {
        $this->emit($model, 'deleted');
    }

    private function emit(Model $model, string $action): void
    {
        $map = config('realtime.models.'.get_class($model));
        if (!is_array($map)) {
            return;
        }

        $this->realtime->entityChanged(
            module: (string) $map['module'],
            entity: (string) $map['entity'],
            action: $action,
            id: $model->getKey(),
            scope: $this->resolveScope($model),
            metadata: $this->resolveMetadata($model),
        );
    }

    private function isDisabled(Model $model): bool
    {
        return $model->wasChanged('estado') && strtoupper((string) $model->getAttribute('estado')) === 'INACTIVO';
    }

    private function resolveScope(Model $model): ?string
    {
        foreach (['tarifa_id', 'categoria_id', 'paquete_id'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        foreach (['codigo', 'codigo_comprobante', 'servicio_codigo', 'numero_cuenta'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function resolveMetadata(Model $model): array
    {
        $metadata = [];

        foreach (['codigo', 'descripcion', 'estado', 'tarifa_id', 'categoria_id', 'subcategoria_id'] as $attribute) {
            if ($model->getAttribute($attribute) !== null) {
                $metadata[$attribute] = $model->getAttribute($attribute);
            }
        }

        return $metadata;
    }
}
