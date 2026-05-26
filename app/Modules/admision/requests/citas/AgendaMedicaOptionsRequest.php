<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaMedicaOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'especialidad_id' => ['nullable', 'integer', Rule::exists('especialidades', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'medico_id' => ['nullable', 'integer', Rule::exists('medicos', 'id')->where('estado', RecordStatus::ACTIVO->value)],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Selecciona una fecha para consultar la agenda médica.',
            'fecha.date' => 'La fecha de consulta de agenda no tiene un formato válido.',
            'especialidad_id.integer' => 'La especialidad seleccionada no es válida.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe o no está activa.',
            'medico_id.integer' => 'El médico seleccionado no es válido.',
            'medico_id.exists' => 'El médico seleccionado no existe o no está activo.',
        ];
    }
}
