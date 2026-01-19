<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaServicioUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'max:255'],
            'nomenclador' => ['nullable', 'string', 'max:50'],

            'precio_sin_igv' => ['required', 'numeric', 'min:0'],
            'unidad' => ['required', 'numeric', 'min:0.001'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nomenclador')) {
            $raw = (string)$this->input('nomenclador');
            $x = strtoupper(trim($raw));
            if ($x === '' || $x === 'NULL') {
                $this->merge(['nomenclador' => null]);
            } else {
                $this->merge(['nomenclador' => $x]);
            }
        }
    }
}
