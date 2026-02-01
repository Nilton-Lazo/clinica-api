<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaMedicaSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'especialidad_id' => ['required', 'integer', Rule::exists('especialidades', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'medico_id' => ['required', 'integer', Rule::exists('medicos', 'id')->where('estado', RecordStatus::ACTIVO->value)],
        ];
    }
}
