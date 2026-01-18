<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],
            'fecha_creacion' => ['prohibited'],

            'requiere_acreditacion' => ['required', 'boolean'],
            'tarifa_base' => ['required', 'boolean'],

            'descripcion_tarifa' => ['required', 'string', 'max:255'],

            'iafa_id' => [
                'nullable',
                'integer',
                Rule::exists('iafas', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],

            'factor_clinica' => ['sometimes', 'numeric', 'min:0'],
            'factor_laboratorio' => ['sometimes', 'numeric', 'min:0'],
            'factor_ecografia' => ['sometimes', 'numeric', 'min:0'],
            'factor_procedimientos' => ['sometimes', 'numeric', 'min:0'],
            'factor_rayos_x' => ['sometimes', 'numeric', 'min:0'],
            'factor_tomografia' => ['sometimes', 'numeric', 'min:0'],
            'factor_patologia' => ['sometimes', 'numeric', 'min:0'],
            'factor_medicina_fisica' => ['sometimes', 'numeric', 'min:0'],
            'factor_resonancia' => ['sometimes', 'numeric', 'min:0'],
            'factor_honorarios_medicos' => ['sometimes', 'numeric', 'min:0'],
            'factor_medicinas' => ['sometimes', 'numeric', 'min:0'],
            'factor_equipos_oxigeno' => ['sometimes', 'numeric', 'min:0'],
            'factor_banco_sangre' => ['sometimes', 'numeric', 'min:0'],
            'factor_mamografia' => ['sometimes', 'numeric', 'min:0'],
            'factor_densitometria' => ['sometimes', 'numeric', 'min:0'],
            'factor_psicoprofilaxis' => ['sometimes', 'numeric', 'min:0'],
            'factor_otros_servicios' => ['sometimes', 'numeric', 'min:0'],
            'factor_medicamentos_comerciales' => ['sometimes', 'numeric', 'min:0'],
            'factor_medicamentos_genericos' => ['sometimes', 'numeric', 'min:0'],
            'factor_material_medico' => ['sometimes', 'numeric', 'min:0'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descripcion_tarifa')) {
            $this->merge(['descripcion_tarifa' => trim((string)$this->input('descripcion_tarifa'))]);
        }

        foreach (['requiere_acreditacion', 'tarifa_base'] as $k) {
            if ($this->has($k)) {
                $this->merge([$k => filter_var($this->input($k), FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        if ($this->has('iafa_id')) {
            $v = trim((string)$this->input('iafa_id'));
            $this->merge(['iafa_id' => $v !== '' ? (int)$v : null]);
        }

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        } else {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}
