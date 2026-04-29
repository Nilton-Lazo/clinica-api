<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\RecordStatus;
use App\Core\support\TipoProgramacionMedica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramacionMedicaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],

            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'consultorio_id' => ['required', 'integer', 'exists:consultorios,id'],
            'turno_id' => ['required', 'integer', 'exists:turnos,id'],

            'tipo' => ['required', 'string', Rule::in(TipoProgramacionMedica::values())],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Selecciona la fecha de la programación médica.',
            'fecha.date' => 'La fecha de programación médica no tiene un formato válido.',
            'especialidad_id.required' => 'Selecciona la especialidad de la programación médica.',
            'especialidad_id.integer' => 'La especialidad seleccionada no es válida.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            'medico_id.required' => 'Selecciona el médico de la programación médica.',
            'medico_id.integer' => 'El médico seleccionado no es válido.',
            'medico_id.exists' => 'El médico seleccionado no existe.',
            'consultorio_id.required' => 'Selecciona el consultorio de la programación médica.',
            'consultorio_id.integer' => 'El consultorio seleccionado no es válido.',
            'consultorio_id.exists' => 'El consultorio seleccionado no existe.',
            'turno_id.required' => 'Selecciona el turno de la programación médica.',
            'turno_id.integer' => 'El turno seleccionado no es válido.',
            'turno_id.exists' => 'El turno seleccionado no existe.',
            'tipo.required' => 'Selecciona el tipo de programación médica.',
            'tipo.string' => 'El tipo de programación médica debe ser texto.',
            'tipo.in' => 'El tipo de programación médica seleccionado no es válido.',
            'estado.required' => 'Selecciona el estado de la programación médica.',
            'estado.string' => 'El estado de la programación médica debe ser texto.',
            'estado.in' => 'El estado de la programación médica no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo')) {
            $this->merge(['tipo' => strtoupper(trim((string)$this->input('tipo')))]);
        }
    }
}
