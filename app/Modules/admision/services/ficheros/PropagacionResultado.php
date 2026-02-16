<?php

namespace App\Modules\admision\services\ficheros;

final class PropagacionResultado
{
    /** @var array{tipo: string, tarifa_id: int, tarifa_codigo: string, tarifa_descripcion: string, mensaje: string, codigo_base?: string, codigo_usado?: string}[] */
    public array $creados = [];

    /** @var array{tipo: string, tarifa_id: int, tarifa_codigo: string, tarifa_descripcion: string, mensaje: string}[] */
    public array $omitidos = [];

    /** @var array{tipo: string, tarifa_id: int, tarifa_codigo: string, tarifa_descripcion: string, mensaje: string, codigo_base: string, codigo_usado: string}[] */
    public array $creadosConCodigoDiferente = [];

    public function toArray(): array
    {
        $hasAlerts = !empty($this->omitidos) || !empty($this->creadosConCodigoDiferente);
        return [
            'propagados' => count($this->creados),
            'omitidos' => count($this->omitidos),
            'creados_con_codigo_diferente' => count($this->creadosConCodigoDiferente),
            'detalle' => [
                'creados' => $this->creados,
                'omitidos' => $this->omitidos,
                'creados_con_codigo_diferente' => $this->creadosConCodigoDiferente,
            ],
            'tiene_alertas' => $hasAlerts,
        ];
    }
}
