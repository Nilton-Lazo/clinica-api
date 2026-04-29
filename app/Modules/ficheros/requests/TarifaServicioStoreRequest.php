<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifaServicioStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'min:1'],
            'subcategoria_id' => ['required', 'integer', 'min:1'],

            'descripcion' => ['required', 'string', 'max:255'],
            'nomenclador' => ['nullable', 'string', 'max:50'],

            'precio_sin_igv' => ['required', 'numeric', 'min:0'],
            'unidad' => ['required', 'numeric', 'min:0'],
            'grupo_codigo' => ['nullable', 'string', 'max:20'],

            'desea_liberar_precio' => ['sometimes', 'boolean'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'Selecciona la categoría del servicio.',
            'categoria_id.integer' => 'Selecciona una categoría válida para el servicio.',
            'categoria_id.min' => 'Selecciona una categoría válida para el servicio.',
            'subcategoria_id.required' => 'Selecciona la subcategoría del servicio.',
            'subcategoria_id.integer' => 'Selecciona una subcategoría válida para el servicio.',
            'subcategoria_id.min' => 'Selecciona una subcategoría válida para el servicio.',
            'descripcion.required' => 'Ingresa la descripción del servicio.',
            'descripcion.string' => 'La descripción del servicio debe ser texto.',
            'descripcion.max' => 'La descripción del servicio no debe superar 255 caracteres.',
            'nomenclador.max' => 'El nomenclador del servicio no debe superar 50 caracteres.',
            'precio_sin_igv.required' => 'Ingresa el precio sin IGV del servicio.',
            'precio_sin_igv.numeric' => 'El precio sin IGV del servicio debe ser numérico.',
            'precio_sin_igv.min' => 'El precio sin IGV del servicio no puede ser negativo.',
            'unidad.required' => 'Ingresa la unidad del servicio.',
            'unidad.numeric' => 'La unidad del servicio debe ser numérica.',
            'unidad.min' => 'La unidad del servicio no puede ser negativa.',
            'grupo_codigo.max' => 'El grupo del servicio no debe superar 20 caracteres.',
            'desea_liberar_precio.boolean' => 'Indica si desea liberar precio con un valor válido.',
            'estado.in' => 'El estado del servicio debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }

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

