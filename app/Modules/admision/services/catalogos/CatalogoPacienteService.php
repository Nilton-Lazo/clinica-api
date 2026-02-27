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
use Illuminate\Support\Facades\Cache;

class CatalogoPacienteService
{
    private const CACHE_TTL_PACIENTE_FORM = 86400;

    private const CACHE_TTL_UBIGEOS_FIRST = 3600;

    public function pacienteForm(): array
    {
        return Cache::remember('admision.catalogos.paciente_form', self::CACHE_TTL_PACIENTE_FORM, function () {
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
        });
    }

    public function paises(array $filters): LengthAwarePaginator
    {
        return $this->paisesUncached($filters);
    }

    /**
     * Lista completa de paises activos para combos (nacionalidad).
     * Cache 1h; en entornos enterprise se evita paginación en este uso.
     */
    public function paisesList(): array
    {
        return Cache::remember('admision.catalogos.paises_list', 3600, function () {
            return Pais::query()
                ->activos()
                ->orderBy('nombre', 'asc')
                ->get(['iso2', 'nombre', 'estado'])
                ->toArray();
        });
    }

    public function ubigeos(array $filters): LengthAwarePaginator
    {
        return $this->ubigeosUncached($filters);
    }

    /**
     * Primera página de ubigeos para combos del wizard. Cache 1h para respuesta inmediata.
     */
    public function ubigeosFirstPage(int $perPage = 250): array
    {
        $perPage = max(1, min(500, $perPage));
        $key = 'admision.catalogos.ubigeos_first_' . $perPage;

        return Cache::remember($key, self::CACHE_TTL_UBIGEOS_FIRST, function () use ($perPage) {
            return Ubigeo::query()
                ->activos()
                ->orderBy('departamento', 'asc')
                ->orderBy('provincia', 'asc')
                ->orderBy('distrito', 'asc')
                ->limit($perPage)
                ->get(['codigo', 'departamento', 'provincia', 'distrito', 'estado'])
                ->toArray();
        });
    }

    private function paisesUncached(array $filters): LengthAwarePaginator
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

    private function ubigeosUncached(array $filters): LengthAwarePaginator
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
