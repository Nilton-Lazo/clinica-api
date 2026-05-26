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

    public function messages(): array
    {
        return [
            'programacion_medica_id.required' => 'Selecciona una programación médica para crear la cita.',
            'programacion_medica_id.integer' => 'La programación médica seleccionada no es válida.',
            'programacion_medica_id.exists' => 'La programación médica seleccionada no existe.',
            'paciente_id.required' => 'Selecciona un paciente para crear la cita.',
            'paciente_id.integer' => 'El paciente seleccionado no es válido.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'hora.required' => 'Selecciona la hora de la cita.',
            'hora.date_format' => 'La hora de la cita debe tener el formato HH:mm.',
            'motivo.string' => 'El motivo de la cita debe ser texto.',
            'motivo.max' => 'El motivo de la cita no debe superar 120 caracteres.',
            'observacion.string' => 'La observación de la cita debe ser texto.',
            'observacion.max' => 'La observación de la cita no debe superar 2000 caracteres.',
            'autorizacion_siteds.string' => 'La autorización SITEDS debe ser texto.',
            'autorizacion_siteds.max' => 'La autorización SITEDS no debe superar 60 caracteres.',
            'cuenta.string' => 'La cuenta de la cita debe ser texto.',
            'cuenta.max' => 'La cuenta de la cita no debe superar 120 caracteres.',
            'iafa_id.integer' => 'La IAFAS seleccionada no es válida.',
            'iafa_id.exists' => 'La IAFAS seleccionada no existe o no está activa.',
            'estado.in' => 'El estado de la cita no es válido.',
        ];
    }
}
