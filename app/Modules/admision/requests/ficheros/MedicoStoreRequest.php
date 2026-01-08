<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use App\Core\support\TipoProfesionalClinica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z\.\-_]+$/', 'unique:medicos,codigo'],

            'cmp' => ['nullable', 'string', 'max:20', 'unique:medicos,cmp'],
            'rne' => ['nullable', 'string', 'max:20', 'unique:medicos,rne'],
            'dni' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z\-\.\s]+$/'],

            'tipo_profesional_clinica' => ['sometimes', 'string', Rule::in(TipoProfesionalClinica::values())],

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

            'adicionales' => ['nullable', 'integer', 'min:0'],
            'extras' => ['nullable', 'integer', 'min:0'],
            'tiempo_promedio_por_paciente' => ['nullable', 'integer', 'min:0'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }

        if (!$this->has('tipo_profesional_clinica') || $this->input('tipo_profesional_clinica') === null || $this->input('tipo_profesional_clinica') === '') {
            $this->merge(['tipo_profesional_clinica' => TipoProfesionalClinica::STAFF->value]);
        }

        if (!$this->has('adicionales') || $this->input('adicionales') === null || $this->input('adicionales') === '') {
            $this->merge(['adicionales' => 0]);
        }

        if (!$this->has('extras') || $this->input('extras') === null || $this->input('extras') === '') {
            $this->merge(['extras' => 0]);
        }

        if (!$this->has('tiempo_promedio_por_paciente') || $this->input('tiempo_promedio_por_paciente') === null || $this->input('tiempo_promedio_por_paciente') === '') {
            $this->merge(['tiempo_promedio_por_paciente' => 0]);
        }
    }
}
