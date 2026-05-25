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
        return Clinica::query()
            ->activos()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function actualOrFail(): Clinica
    {
        $activeCount = Clinica::query()->activos()->count();
        if ($activeCount > 1) {
            throw ValidationException::withMessages([
                'clinica' => [
                    'Hay más de una clínica activa configurada. Deja una sola clínica activa antes de generar reportes.',
                ],
            ]);
        }

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
        return InstitutionReportContext::fromClinica($this->actualOrFail());
    }

    public function forgetCache(): void
    {
        Cache::forget('clinica:actual');
        Cache::forget('clinica:report-context');
    }
}
