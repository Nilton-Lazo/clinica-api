<?php

namespace App\Modules\admision\requests\citas;

use App\Core\support\ModalidadFechasProgramacion;
use App\Core\support\RecordStatus;
use App\Core\support\TipoProgramacionMedica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramacionMedicaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'modalidad_fechas' => ['required', 'string', Rule::in(ModalidadFechasProgramacion::values())],

            'fecha' => ['nullable', 'date'],
            'fechas' => ['nullable', 'array', 'min:1'],
            'fechas.*' => ['date'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],

            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'consultorio_id' => ['required', 'integer', 'exists:consultorios,id'],
            'turno_id' => ['required', 'integer', 'exists:turnos,id'],

            'tipo' => ['required', 'string', Rule::in(TipoProgramacionMedica::values())],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'modalidad_fechas.required' => 'Selecciona la modalidad de fechas para crear la programación médica.',
            'modalidad_fechas.string' => 'La modalidad de fechas debe ser texto.',
            'modalidad_fechas.in' => 'La modalidad de fechas seleccionada no es válida.',
            'fecha.date' => 'La fecha de programación no tiene un formato válido.',
            'fechas.array' => 'La lista de fechas de programación tiene un formato inválido.',
            'fechas.min' => 'Selecciona al menos una fecha para la modalidad aleatoria.',
            'fechas.*.date' => 'Una de las fechas seleccionadas no tiene un formato válido.',
            'fecha_inicio.date' => 'La fecha inicial del rango no tiene un formato válido.',
            'fecha_fin.date' => 'La fecha final del rango no tiene un formato válido.',
            'especialidad_id.required' => 'Selecciona la especialidad de la programación médica.',
            'especialidad_id.integer' => 'La especialidad seleccionada no es válida.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            'medico_id.required' => 'Selecciona el médico de la programación médica.',
            'medico_id.integer' => 'El médico seleccionado no es válido.',
            'medico_id.exists' => 'El médico seleccionado no existe.',
            'consultorio_id.required' => 'Selecciona el consultorio de la programación médica.',
            'consultorio_id.integer' => 'El consultorio seleccionado no es válido.',
            'consultorio_id.exists' => 'El consultorio seleccionado no existe.',
            'turno_id.required' => 'Selecciona el turno de la programación médica.',
            'turno_id.integer' => 'El turno seleccionado no es válido.',
            'turno_id.exists' => 'El turno seleccionado no existe.',
            'tipo.required' => 'Selecciona el tipo de programación médica.',
            'tipo.string' => 'El tipo de programación médica debe ser texto.',
            'tipo.in' => 'El tipo de programación médica seleccionado no es válido.',
            'estado.string' => 'El estado de la programación médica debe ser texto.',
            'estado.in' => 'El estado de la programación médica no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('modalidad_fechas')) {
            $this->merge(['modalidad_fechas' => strtoupper(trim((string)$this->input('modalidad_fechas')))]);
        }

        if ($this->has('tipo')) {
            $this->merge(['tipo' => strtoupper(trim((string)$this->input('tipo')))]);
        }

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $m = strtoupper((string)$this->input('modalidad_fechas'));

            if ($m === ModalidadFechasProgramacion::DIARIA->value) {
                if (!$this->filled('fecha')) {
                    $v->errors()->add('fecha', 'Selecciona la fecha para crear la programación médica diaria.');
                }
            }

            if ($m === ModalidadFechasProgramacion::ALEATORIA->value) {
                $fechas = $this->input('fechas');
                if (!is_array($fechas) || count($fechas) < 1) {
                    $v->errors()->add('fechas', 'Selecciona al menos una fecha para crear programaciones aleatorias.');
                } else {
                    $norm = array_values(array_unique($fechas));
                    if (count($norm) !== count($fechas)) {
                        $v->errors()->add('fechas', 'No repitas fechas en la programación médica aleatoria.');
                    }
                }
            }

            if ($m === ModalidadFechasProgramacion::RANGO->value) {
                if (!$this->filled('fecha_inicio')) {
                    $v->errors()->add('fecha_inicio', 'Selecciona la fecha inicial del rango de programación médica.');
                }
                if (!$this->filled('fecha_fin')) {
                    $v->errors()->add('fecha_fin', 'Selecciona la fecha final del rango de programación médica.');
                }

                if ($this->filled('fecha_inicio') && $this->filled('fecha_fin')) {
                    $ini = (string)$this->input('fecha_inicio');
                    $fin = (string)$this->input('fecha_fin');
                    if ($fin < $ini) {
                        $v->errors()->add('fecha_fin', 'La fecha final del rango no puede ser anterior a la fecha inicial.');
                    }
                }
            }
        });
    }
}
