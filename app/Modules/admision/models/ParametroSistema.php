<?php

namespace App\Modules\admision\models;

use Illuminate\Database\Eloquent\Model;

class ParametroSistema extends Model
{
    protected $table = 'parametros_sistema';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function getValor(string $clave, string $default = ''): string
    {
        $p = self::query()->where('clave', $clave)->first();
        return $p ? (string)$p->valor : $default;
    }

    public static function setValor(string $clave, string $valor, ?string $descripcion = null): void
    {
        $attrs = ['valor' => $valor, 'updated_at' => now()];
        if ($descripcion !== null) {
            $attrs['descripcion'] = $descripcion;
        }
        $exists = self::query()->where('clave', $clave)->exists();
        if (!$exists) {
            $attrs['created_at'] = now();
        }
        self::query()->updateOrInsert(['clave' => $clave], $attrs);
    }

    public static function getIgvPorcentaje(): float
    {
        $v = self::getValor('igv_porcentaje', '18');
        $n = (float)$v;
        return $n >= 0 && $n <= 100 ? $n : 18.0;
    }
}
