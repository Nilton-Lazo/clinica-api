<?php

namespace App\Modules\admision\services\catalogos;

use App\Core\support\EstadoCivilPaciente;
use App\Core\support\MedioInformacionPaciente;
use App\Core\support\OcupacionPaciente;
use App\Core\support\ParentescoEmergencia;
use App\Core\support\ParentescoSeguroPaciente;
use App\Core\support\SexoPaciente;
use App\Core\support\TipoDocumentoPaciente;
use App\Core\support\TipoPaciente;
use App\Core\support\TipoSangre;
use App\Modules\admision\models\Pais;
use App\Modules\admision\models\Ubigeo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogoPacienteService
{
    public function pacienteForm(): array
    {
        return [
            'tipo_documento' => TipoDocumentoPaciente::values(),
            'estado_civil' => EstadoCivilPaciente::values(),
            'sexo' => SexoPaciente::values(),
            'parentesco_seguro' => ParentescoSeguroPaciente::values(),
            'parentesco_emergencia' => ParentescoEmergencia::values(),
            'tipo_paciente' => TipoPaciente::values(),
            'ocupacion' => OcupacionPaciente::values(),
            'medio_informacion' => MedioInformacionPaciente::values(),
            'tipo_sangre' => TipoSangre::values(),
        ];
    }

    public function paises(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(200, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;

        $query = Pais::query()->activos();

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('iso2', 'ilike', "%{$q}%")
                    ->orWhere('nombre', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderBy('nombre', 'asc')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q]);
    }

    public function ubigeos(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(200, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;

        $query = Ubigeo::query()->activos();

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('departamento', 'ilike', "%{$q}%")
                    ->orWhere('provincia', 'ilike', "%{$q}%")
                    ->orWhere('distrito', 'ilike', "%{$q}%");
            });
        }

        return $query
            ->orderBy('departamento', 'asc')
            ->orderBy('provincia', 'asc')
            ->orderBy('distrito', 'asc')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q]);
    }
}
