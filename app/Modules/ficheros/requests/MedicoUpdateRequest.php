<?php

namespace App\Modules\ficheros\requests;

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

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del médico lo genera el sistema; no lo modifiques manualmente.',
            'cmp.max' => 'El CMP del médico no debe superar 20 caracteres.',
            'cmp.unique' => 'Ya existe otro médico registrado con ese CMP.',
            'rne.max' => 'El RNE del médico no debe superar 20 caracteres.',
            'rne.unique' => 'Ya existe otro médico registrado con ese RNE.',
            'dni.max' => 'El DNI del médico no debe superar 20 caracteres.',
            'dni.regex' => 'El DNI del médico solo puede contener letras, números, espacios, punto o guion.',
            'tipo_profesional_clinica.required' => 'Selecciona el tipo de profesional clínico del médico.',
            'tipo_profesional_clinica.in' => 'Selecciona un tipo de profesional clínico válido.',
            'nombres.required' => 'Ingresa los nombres del médico.',
            'nombres.max' => 'Los nombres del médico no deben superar 120 caracteres.',
            'apellido_paterno.required' => 'Ingresa el apellido paterno del médico.',
            'apellido_paterno.max' => 'El apellido paterno del médico no debe superar 120 caracteres.',
            'apellido_materno.required' => 'Ingresa el apellido materno del médico.',
            'apellido_materno.max' => 'El apellido materno del médico no debe superar 120 caracteres.',
            'direccion.max' => 'La dirección del médico no debe superar 255 caracteres.',
            'centro_trabajo.max' => 'El centro de trabajo del médico no debe superar 255 caracteres.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento del médico no tiene un formato válido.',
            'ruc.size' => 'El RUC del médico debe tener 11 dígitos.',
            'ruc.regex' => 'El RUC del médico debe contener solo números.',
            'especialidad_id.required' => 'Selecciona la especialidad del médico.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe o ya no está disponible.',
            'telefono.max' => 'El teléfono del médico no debe superar 30 caracteres.',
            'telefono_02.max' => 'El teléfono secundario del médico no debe superar 30 caracteres.',
            'email.email' => 'Ingresa un correo electrónico válido para el médico.',
            'email.max' => 'El correo electrónico del médico no debe superar 255 caracteres.',
            'adicionales.required' => 'Indica los adicionales del médico.',
            'adicionales.integer' => 'Los adicionales del médico deben ser un número entero.',
            'adicionales.min' => 'Los adicionales del médico no pueden ser negativos.',
            'extras.required' => 'Indica los extras del médico.',
            'extras.integer' => 'Los extras del médico deben ser un número entero.',
            'extras.min' => 'Los extras del médico no pueden ser negativos.',
            'tiempo_promedio_por_paciente.required' => 'Indica el tiempo promedio por paciente.',
            'tiempo_promedio_por_paciente.integer' => 'El tiempo promedio por paciente debe ser un número entero.',
            'tiempo_promedio_por_paciente.min' => 'El tiempo promedio por paciente no puede ser negativo.',
            'estado.required' => 'Selecciona el estado del médico.',
            'estado.in' => 'El estado del médico debe ser ACTIVO o INACTIVO.',
        ];
    }
}

