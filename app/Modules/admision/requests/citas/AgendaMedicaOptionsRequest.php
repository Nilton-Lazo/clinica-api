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
}
