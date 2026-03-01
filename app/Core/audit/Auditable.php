<?php

namespace App\Core\audit;

use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static array $defaultAuditExclude = ['password', 'remember_token'];

    public static function bootAuditable(): void
    {
        static::creating(function (Model $model): void {
            self::captureModelAudit($model, 'created', [], self::attributesForAudit($model->getAttributes(), $model));
        });

        static::updating(function (Model $model): void {
            self::captureModelAudit(
                $model,
                'updated',
                self::attributesForAudit($model->getOriginal(), $model),
                self::attributesForAudit($model->getAttributes(), $model)
            );
        });

        static::deleting(function (Model $model): void {
            $raw = $model->getOriginal() ?: $model->getAttributes();
            self::captureModelAudit(
                $model,
                'deleted',
                self::attributesForAudit($raw, $model),
                []
            );
        });
    }

    protected static function captureModelAudit(Model $model, string $event, array $oldValues, array $newValues): void
    {
        if (! app()->bound(AuditService::class)) {
            return;
        }

        $request = request();
        if ($request === null && app()->runningInConsole()) {
            return;
        }

        $service = app(AuditService::class);
        $displayName = method_exists($model, 'resolveAuditableDisplayName')
            ? $model->resolveAuditableDisplayName()
            : self::defaultAuditableDisplayName($model);

        $service->logModelEvent($event, $model, $oldValues, $newValues, $displayName);
    }

    protected static function attributesForAudit(array $attributes, Model $model): array
    {
        $exclude = self::$defaultAuditExclude;
        if (property_exists($model, 'auditExclude') && is_array($model->auditExclude)) {
            $exclude = array_merge($exclude, $model->auditExclude);
        }
        if (property_exists($model, 'auditOnly') && is_array($model->auditOnly)) {
            return array_intersect_key($attributes, array_flip($model->auditOnly));
        }
        return array_diff_key($attributes, array_flip($exclude));
    }

    protected static function defaultAuditableDisplayName(Model $model): string
    {
        $key = $model->getKey();
        $basename = class_basename($model);
        return $key !== null ? $basename . ' #' . $key : $basename . ' (nuevo)';
    }
}
