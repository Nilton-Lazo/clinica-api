<?php

namespace App\Modules\ficheros\requests;

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

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código de la tarifa lo genera el sistema; no lo envíes manualmente.',
            'fecha_creacion.prohibited' => 'La fecha de creación de la tarifa la registra el sistema; no la envíes manualmente.',
            'requiere_acreditacion.required' => 'Indica si la tarifa requiere acreditación.',
            'requiere_acreditacion.boolean' => 'El indicador de acreditación de la tarifa debe ser verdadero o falso.',
            'tarifa_base.required' => 'Indica si la tarifa será tarifario base.',
            'tarifa_base.boolean' => 'El indicador de tarifario base debe ser verdadero o falso.',
            'descripcion_tarifa.required' => 'Ingresa la descripción de la tarifa.',
            'descripcion_tarifa.string' => 'La descripción de la tarifa debe ser texto.',
            'descripcion_tarifa.max' => 'La descripción de la tarifa no debe superar 255 caracteres.',
            'iafa_id.integer' => 'Selecciona una IAFAS válida para la tarifa.',
            'iafa_id.exists' => 'La IAFAS seleccionada no existe o está inactiva.',
            'factor_clinica.numeric' => 'El factor clínica debe ser numérico.',
            'factor_clinica.min' => 'El factor clínica no puede ser negativo.',
            'factor_laboratorio.numeric' => 'El factor laboratorio debe ser numérico.',
            'factor_laboratorio.min' => 'El factor laboratorio no puede ser negativo.',
            'factor_ecografia.numeric' => 'El factor ecografía debe ser numérico.',
            'factor_ecografia.min' => 'El factor ecografía no puede ser negativo.',
            'factor_procedimientos.numeric' => 'El factor procedimientos debe ser numérico.',
            'factor_procedimientos.min' => 'El factor procedimientos no puede ser negativo.',
            'factor_rayos_x.numeric' => 'El factor rayos X debe ser numérico.',
            'factor_rayos_x.min' => 'El factor rayos X no puede ser negativo.',
            'factor_tomografia.numeric' => 'El factor tomografía debe ser numérico.',
            'factor_tomografia.min' => 'El factor tomografía no puede ser negativo.',
            'factor_patologia.numeric' => 'El factor patología debe ser numérico.',
            'factor_patologia.min' => 'El factor patología no puede ser negativo.',
            'factor_medicina_fisica.numeric' => 'El factor medicina física debe ser numérico.',
            'factor_medicina_fisica.min' => 'El factor medicina física no puede ser negativo.',
            'factor_resonancia.numeric' => 'El factor resonancia debe ser numérico.',
            'factor_resonancia.min' => 'El factor resonancia no puede ser negativo.',
            'factor_honorarios_medicos.numeric' => 'El factor honorarios médicos debe ser numérico.',
            'factor_honorarios_medicos.min' => 'El factor honorarios médicos no puede ser negativo.',
            'factor_medicinas.numeric' => 'El factor medicinas debe ser numérico.',
            'factor_medicinas.min' => 'El factor medicinas no puede ser negativo.',
            'factor_equipos_oxigeno.numeric' => 'El factor equipos de oxígeno debe ser numérico.',
            'factor_equipos_oxigeno.min' => 'El factor equipos de oxígeno no puede ser negativo.',
            'factor_banco_sangre.numeric' => 'El factor banco de sangre debe ser numérico.',
            'factor_banco_sangre.min' => 'El factor banco de sangre no puede ser negativo.',
            'factor_mamografia.numeric' => 'El factor mamografía debe ser numérico.',
            'factor_mamografia.min' => 'El factor mamografía no puede ser negativo.',
            'factor_densitometria.numeric' => 'El factor densitometría debe ser numérico.',
            'factor_densitometria.min' => 'El factor densitometría no puede ser negativo.',
            'factor_psicoprofilaxis.numeric' => 'El factor psicoprofilaxis debe ser numérico.',
            'factor_psicoprofilaxis.min' => 'El factor psicoprofilaxis no puede ser negativo.',
            'factor_otros_servicios.numeric' => 'El factor otros servicios debe ser numérico.',
            'factor_otros_servicios.min' => 'El factor otros servicios no puede ser negativo.',
            'factor_medicamentos_comerciales.numeric' => 'El factor medicamentos comerciales debe ser numérico.',
            'factor_medicamentos_comerciales.min' => 'El factor medicamentos comerciales no puede ser negativo.',
            'factor_medicamentos_genericos.numeric' => 'El factor medicamentos genéricos debe ser numérico.',
            'factor_medicamentos_genericos.min' => 'El factor medicamentos genéricos no puede ser negativo.',
            'factor_material_medico.numeric' => 'El factor material médico debe ser numérico.',
            'factor_material_medico.min' => 'El factor material médico no puede ser negativo.',
            'estado.in' => 'El estado de la tarifa debe ser ACTIVO o INACTIVO.',
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

