<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaMedioPagoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50', 'unique:caja_medios_pago,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
            'forma_pago_ids' => ['required', 'array', 'min:1'],
            'forma_pago_ids.*' => ['integer', Rule::exists('caja_formas_pago', 'id')->where('estado', RecordStatus::ACTIVO->value)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}
