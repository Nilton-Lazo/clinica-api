<?php

namespace App\Core\clinica\services;

use App\Core\clinica\models\Clinica;
use App\Core\reporting\InstitutionReportContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ClinicaConfigService
{
    public function actual(): ?Clinica
    {
        $ttl = max(60, (int) config('reports.institution_cache_ttl_seconds', 300));

        return Cache::remember('clinica:actual', $ttl, function () {
            return Clinica::query()
                ->activos()
                ->orderBy('id')
                ->first();
        });
    }

    public function actualOrFail(): Clinica
    {
        $row = $this->actual();
        if ($row === null) {
            throw ValidationException::withMessages([
                'clinica' => [
                    'No hay datos de la clínica configurados. Registra una fila activa en la tabla clinica antes de generar reportes.',
                ],
            ]);
        }

        return $row;
    }

    public function reportContext(): InstitutionReportContext
    {
        $ttl = max(60, (int) config('reports.institution_cache_ttl_seconds', 300));

        return Cache::remember('clinica:report-context', $ttl, function () {
            return InstitutionReportContext::fromClinica($this->actualOrFail());
        });
    }

    public function forgetCache(): void
    {
        Cache::forget('clinica:actual');
        Cache::forget('clinica:report-context');
    }
}
