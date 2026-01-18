<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IafaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],

            'tipo_iafa_id' => [
                'required',
                'integer',
                Rule::exists('tipos_iafas', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],

            'razon_social' => ['required', 'string', 'max:255'],
            'descripcion_corta' => ['required', 'string', 'max:120'],
            'ruc' => ['required', 'string', 'regex:/^\d{11}$/'],

            'direccion' => ['nullable', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'pagina_web' => ['nullable', 'string', 'max:200'],

            'fecha_inicio_cobertura' => ['required', 'date'],
            'fecha_fin_cobertura' => ['required', 'date', 'after_or_equal:fecha_inicio_cobertura'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'razon_social' => $this->has('razon_social') ? trim((string)$this->input('razon_social')) : null,
            'descripcion_corta' => $this->has('descripcion_corta') ? trim((string)$this->input('descripcion_corta')) : null,
            'ruc' => $this->has('ruc') ? preg_replace('/\s+/', '', trim((string)$this->input('ruc'))) : null,
        ]);

        foreach (['direccion', 'representante_legal', 'telefono', 'pagina_web'] as $k) {
            if ($this->has($k)) {
                $v = trim((string)$this->input($k));
                $this->merge([$k => $v !== '' ? $v : null]);
            }
        }

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        } else {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
        }
    }
}
