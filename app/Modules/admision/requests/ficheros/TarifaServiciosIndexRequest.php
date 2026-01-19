<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaServiciosIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],

            'status' => ['sometimes', 'string', Rule::in(RecordStatus::values())],

            'q' => ['sometimes', 'string', 'max:255'],

            'codigo' => ['sometimes', 'string', 'max:20'],
            'nomenclador' => ['sometimes', 'string', 'max:50'], 

            'categoria_id' => ['sometimes', 'integer', 'min:1'],
            'subcategoria_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'codigo', 'nomenclador'] as $k) {
            if ($this->has($k)) {
                $v = trim((string)$this->input($k));
                $this->merge([$k => $v !== '' ? $v : null]);
            }
        }

        if ($this->has('status')) {
            $this->merge(['status' => strtoupper(trim((string)$this->input('status')))]);
        }
    }
}
