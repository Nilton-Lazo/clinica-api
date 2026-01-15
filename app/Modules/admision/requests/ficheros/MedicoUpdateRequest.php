<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use App\Core\support\TipoProfesionalClinica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('medico')?->id ?? $this->route('medico');

        return [
            'codigo' => ['prohibited'],

            'cmp' => ['nullable', 'string', 'max:20', Rule::unique('medicos', 'cmp')->ignore($id)],
            'rne' => ['nullable', 'string', 'max:20', Rule::unique('medicos', 'rne')->ignore($id)],
            'dni' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-\.\s]+$/'],

            'tipo_profesional_clinica' => ['required', 'string', Rule::in(TipoProfesionalClinica::values())],

            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['required', 'string', 'max:120'],
            'apellido_materno' => ['required', 'string', 'max:120'],

            'direccion' => ['nullable', 'string', 'max:255'],
            'centro_trabajo' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date'],

            'ruc' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]{11}$/'],

            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],

            'telefono' => ['nullable', 'string', 'max:30'],
            'telefono_02' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],

            'adicionales' => ['required', 'integer', 'min:0'],
            'extras' => ['required', 'integer', 'min:0'],
            'tiempo_promedio_por_paciente' => ['required', 'integer', 'min:0'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }
}
