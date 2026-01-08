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

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo')) {
            $this->merge(['tipo' => strtoupper(trim((string)$this->input('tipo')))]);
        }
    }
}
