<?php

namespace App\Modules\admision\services\citas;

use App\Core\support\ComprobanteEmisionCuentaOrigenFilter;
use App\Modules\admision\models\Cuenta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CuentaCitaService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';

        $emisionOrigen = isset($filters['emision_origen']) ? trim((string) $filters['emision_origen']) : '';
        $cuentaOrigines = $emisionOrigen !== ''
            ? ComprobanteEmisionCuentaOrigenFilter::cuentaOriginesFor($emisionOrigen)
            : null;

        $query = Cuenta::query()->orderByDesc('fecha')->orderByDesc('id');

        if (is_array($cuentaOrigines)) {
            if ($cuentaOrigines === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('cuentas.origen', $cuentaOrigines);
            }
        }

        if ($q !== '') {
            $driver = DB::getDriverName();
            $like = $driver === 'pgsql' ? 'ilike' : 'like';
            $pattern = "%{$q}%";

            $query->where(function ($sub) use ($like, $pattern) {
                $sub->where('cuentas.nro_cuenta', $like, $pattern)
                    ->orWhere('cuentas.hc', $like, $pattern)
                    ->orWhere('cuentas.nr', $like, $pattern)
                    ->orWhere('cuentas.paciente_nombre', $like, $pattern);
            });
        }

        return $query
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q]);
    }
}
