<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\JornadaTurno;
use App\Core\support\RecordStatus;
use App\Core\support\TipoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TurnoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],
            'duracion_minutos' => ['prohibited'],

            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'tipo_turno' => ['required', 'string', Rule::in(TipoTurno::values())],
            'jornada' => ['required', 'string', Rule::in(JornadaTurno::values())],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],

            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del turno lo genera el sistema; no lo modifiques manualmente.',
            'duracion_minutos.prohibited' => 'La duración del turno se calcula automáticamente con la hora de inicio y fin.',
            'hora_inicio.required' => 'Ingresa la hora de inicio del turno.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'hora_fin.required' => 'Ingresa la hora de fin del turno.',
            'hora_fin.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'tipo_turno.required' => 'Selecciona el tipo de turno.',
            'tipo_turno.in' => 'Selecciona un tipo de turno válido.',
            'jornada.required' => 'Selecciona la jornada del turno.',
            'jornada.in' => 'Selecciona una jornada válida para el turno.',
            'estado.required' => 'Selecciona el estado del turno.',
            'estado.in' => 'El estado del turno debe ser ACTIVO o INACTIVO.',
            'descripcion.max' => 'La descripción del turno no debe superar 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo_turno')) {
            $this->merge(['tipo_turno' => strtoupper(trim((string)$this->input('tipo_turno')))]);
        }

        if ($this->has('jornada')) {
            $j = strtoupper(trim((string)$this->input('jornada')));
            $j = str_replace('MAÑANA', 'MANANA', $j);
            $this->merge(['jornada' => $j]);
        }

        if ($this->has('descripcion')) {
            $d = trim((string)$this->input('descripcion'));
            $this->merge(['descripcion' => $d !== '' ? $d : null]);
        }
    }
}

