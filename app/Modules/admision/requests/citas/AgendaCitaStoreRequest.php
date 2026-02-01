<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaCitaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'programacion_medica_id' => ['required', 'integer', Rule::exists('programaciones_medicas', 'id')],
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['nullable', 'string', 'max:120'],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'autorizacion_siteds' => ['nullable', 'string', 'max:60'],
            'cuenta' => ['nullable', 'string', 'max:120'],
            'iafa_id' => ['nullable', 'integer', Rule::exists('iafas', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'estado' => ['nullable', Rule::in(RecordStatus::values())],
        ];
    }
}
