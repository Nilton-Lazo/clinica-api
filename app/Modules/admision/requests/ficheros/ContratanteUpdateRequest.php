<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratanteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],

            'razon_social' => ['required', 'string', 'max:255'],

            'ruc' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('razon_social')) {
            $this->merge(['razon_social' => trim((string)$this->input('razon_social'))]);
        }

        foreach (['ruc', 'telefono', 'direccion'] as $k) {
            if ($this->has($k)) {
                $v = trim((string)$this->input($k));
                if ($k === 'ruc') {
                    $v = preg_replace('/\s+/', '', $v);
                }
                $this->merge([$k => $v !== '' ? $v : null]);
            }
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}
