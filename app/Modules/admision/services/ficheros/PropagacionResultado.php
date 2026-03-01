<?php

namespace App\Modules\admision\services\ficheros;

final class PropagacionResultado
{

    public array $creados = [];

    public array $omitidos = [];

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
