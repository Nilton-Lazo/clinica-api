<?php

namespace App\Modules\ficheros\requests;

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

            'hora' => ['sometimes', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'La cantidad de registros por página debe ser un número entero.',
            'per_page.min' => 'La cantidad de registros por página debe ser al menos 1.',
            'per_page.max' => 'La cantidad de registros por página no debe superar 100.',
            'page.integer' => 'La página solicitada debe ser un número entero.',
            'page.min' => 'La página solicitada debe ser al menos 1.',
            'status.in' => 'El estado para filtrar servicios debe ser ACTIVO, INACTIVO o SUSPENDIDO.',
            'q.max' => 'La búsqueda no debe superar 255 caracteres.',
            'codigo.max' => 'El código de servicio no debe superar 20 caracteres.',
            'nomenclador.max' => 'El nomenclador no debe superar 50 caracteres.',
            'categoria_id.integer' => 'Selecciona una categoría válida para filtrar servicios.',
            'categoria_id.min' => 'Selecciona una categoría válida para filtrar servicios.',
            'subcategoria_id.integer' => 'Selecciona una subcategoría válida para filtrar servicios.',
            'subcategoria_id.min' => 'Selecciona una subcategoría válida para filtrar servicios.',
            'hora.regex' => 'La hora de referencia debe tener formato HH:MM o HH:MM:SS.',
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

        if ($this->has('hora')) {
            $h = trim((string)$this->input('hora'));
            if ($h !== '' && str_contains($h, ' ')) {
                $parts = explode(' ', $h);
                $timePart = end($parts);
                if (preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', trim($timePart))) {
                    $this->merge(['hora' => trim($timePart)]);
                }
            }
        }
    }
}

