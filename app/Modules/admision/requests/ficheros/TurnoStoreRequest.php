<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\JornadaTurno;
use App\Core\support\RecordStatus;
use App\Core\support\TipoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TurnoStoreRequest extends FormRequest
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
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],

            'descripcion' => ['nullable', 'string', 'max:255'],
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

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}
