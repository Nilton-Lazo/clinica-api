<?php

namespace App\Modules\ficheros\requests;

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

    public function messages(): array
    {
        return [
            'clone_all.boolean' => 'Indica si deseas clonar todo con un valor válido.',
            'categoria_ids.array' => 'La selección de categorías debe enviarse como una lista.',
            'categoria_ids.*.integer' => 'Cada categoría seleccionada debe tener un identificador válido.',
            'categoria_ids.*.min' => 'Cada categoría seleccionada debe tener un identificador válido.',
            'subcategoria_ids.array' => 'La selección de subcategorías debe enviarse como una lista.',
            'subcategoria_ids.*.integer' => 'Cada subcategoría seleccionada debe tener un identificador válido.',
            'subcategoria_ids.*.min' => 'Cada subcategoría seleccionada debe tener un identificador válido.',
            'servicio_ids.array' => 'La selección de servicios debe enviarse como una lista.',
            'servicio_ids.*.integer' => 'Cada servicio seleccionado debe tener un identificador válido.',
            'servicio_ids.*.min' => 'Cada servicio seleccionado debe tener un identificador válido.',
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
                'selection' => ['Selecciona al menos una categoría, subcategoría o servicio, o usa la opción de clonar todo.'],
            ]);
        }
    }
}

