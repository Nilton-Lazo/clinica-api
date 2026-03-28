<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function messages(): array
    {
        return [
            'dni_o_ruc.required' => 'El DNI o RUC es obligatorio.',
            'dni_o_ruc.regex' => 'El DNI o RUC debe tener 8 dígitos (DNI) o 11 (RUC).',
        ];
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],

            'tipo' => ['required', 'string', Rule::in(['ASISTENCIAL', 'ADMINISTRATIVO'])],

            'nombre' => ['required', 'string', 'max:255'],

            'dni_o_ruc' => ['required', 'string', 'regex:/^(\d{8}|\d{11})$/'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nombre')) {
            $this->merge(['nombre' => trim((string) $this->input('nombre'))]);
        }

        if ($this->has('tipo')) {
            $this->merge(['tipo' => strtoupper(trim((string) $this->input('tipo')))]);
        }

        $dniR = $this->input('dni_o_ruc');
        $dniNorm = $dniR === null || $dniR === '' ? '' : preg_replace('/\s+/', '', trim((string) $dniR));
        $this->merge(['dni_o_ruc' => $dniNorm]);

        foreach (['telefono', 'direccion'] as $k) {
            $v = $this->input($k);
            if ($v === null || $v === '') {
                $this->merge([$k => null]);
                continue;
            }
            $t = trim((string) $v);
            $this->merge([$k => $t === '' ? null : $t]);
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string) $this->input('estado')))]);
        }
    }
}
