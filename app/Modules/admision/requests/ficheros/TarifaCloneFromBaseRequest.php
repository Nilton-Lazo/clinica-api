<?php

namespace App\Modules\admision\requests\ficheros;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class TarifaCloneFromBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'clone_all' => ['sometimes', 'boolean'],

            'categoria_ids' => ['sometimes', 'array'],
            'categoria_ids.*' => ['integer', 'min:1'],

            'subcategoria_ids' => ['sometimes', 'array'],
            'subcategoria_ids.*' => ['integer', 'min:1'],

            'servicio_ids' => ['sometimes', 'array'],
            'servicio_ids.*' => ['integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'clone_all' => filter_var($this->input('clone_all', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    protected function passedValidation(): void
    {
        $cloneAll = (bool)($this->validated('clone_all') ?? false);

        if ($cloneAll) {
            return;
        }

        $cats = $this->validated('categoria_ids') ?? [];
        $subs = $this->validated('subcategoria_ids') ?? [];
        $serv = $this->validated('servicio_ids') ?? [];

        if (count($cats) === 0 && count($subs) === 0 && count($serv) === 0) {
            throw ValidationException::withMessages([
                'selection' => ['Debe enviar clone_all=true o al menos una selección (categoria_ids/subcategoria_ids/servicio_ids).'],
            ]);
        }
    }
}
