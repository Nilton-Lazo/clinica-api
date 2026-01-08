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
                    $v->errors()->add('fecha', 'La fecha es obligatoria en modalidad DIARIA.');
                }
            }

            if ($m === ModalidadFechasProgramacion::ALEATORIA->value) {
                $fechas = $this->input('fechas');
                if (!is_array($fechas) || count($fechas) < 1) {
                    $v->errors()->add('fechas', 'Debe enviar una lista de fechas en modalidad ALEATORIA.');
                } else {
                    $norm = array_values(array_unique($fechas));
                    if (count($norm) !== count($fechas)) {
                        $v->errors()->add('fechas', 'No repita fechas en modalidad ALEATORIA.');
                    }
                }
            }

            if ($m === ModalidadFechasProgramacion::RANGO->value) {
                if (!$this->filled('fecha_inicio')) {
                    $v->errors()->add('fecha_inicio', 'La fecha_inicio es obligatoria en modalidad RANGO.');
                }
                if (!$this->filled('fecha_fin')) {
                    $v->errors()->add('fecha_fin', 'La fecha_fin es obligatoria en modalidad RANGO.');
                }

                if ($this->filled('fecha_inicio') && $this->filled('fecha_fin')) {
                    $ini = (string)$this->input('fecha_inicio');
                    $fin = (string)$this->input('fecha_fin');
                    if ($fin < $ini) {
                        $v->errors()->add('fecha_fin', 'La fecha_fin no puede ser menor que fecha_inicio.');
                    }
                }
            }
        });
    }
}
