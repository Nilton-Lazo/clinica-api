<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaqueteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],

            'descripcion' => ['required', 'string', 'max:255'],

            'tarifa_id' => ['required', 'integer', 'min:1'],

            'precio_sin_igv' => ['required', 'numeric', 'min:0'],

            'vigencia_actual' => ['required', 'date_format:Y-m-d'],

            'dias_hospitalizacion' => ['nullable', 'integer', 'min:0', 'max:2147483647'],

            'cuenta_contabilidad' => ['nullable', 'string', 'max:255'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descripcion')) {
            $this->merge(['descripcion' => trim((string) $this->input('descripcion'))]);
        }

        $cc = $this->input('cuenta_contabilidad');
        if ($cc === null || $cc === '') {
            $this->merge(['cuenta_contabilidad' => null]);
        } elseif ($this->has('cuenta_contabilidad')) {
            $this->merge(['cuenta_contabilidad' => trim((string) $cc) !== '' ? trim((string) $cc) : null]);
        }

        $dh = $this->input('dias_hospitalizacion');
        if ($dh === null || $dh === '') {
            $this->merge(['dias_hospitalizacion' => null]);
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string) $this->input('estado')))]);
        }
    }
}
