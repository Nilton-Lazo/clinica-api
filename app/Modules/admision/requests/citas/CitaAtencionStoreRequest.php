<?php

namespace App\Modules\admision\requests\citas;

use Illuminate\Foundation\Http\FormRequest;

class CitaAtencionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solo_actualizar_datos' => ['sometimes', 'boolean'],
            'acudio_a_su_cita' => ['sometimes', 'boolean'],
            'hora_asistencia' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'paciente_plan_id' => ['nullable', 'integer', 'exists:paciente_planes,id'],
            'parentesco_seguro' => ['nullable', 'string', 'max:30'],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'control_pre_post_natal' => ['sometimes', 'boolean'],
            'control_nino_sano' => ['sometimes', 'boolean'],
            'chequeo' => ['sometimes', 'boolean'],
            'carencia' => ['sometimes', 'boolean'],
            'latencia' => ['sometimes', 'boolean'],
        ];
    }
}
