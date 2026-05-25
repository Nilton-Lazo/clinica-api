<?php

namespace App\Modules\ficheros\requests;

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
            'ruc' => ['required', 'string', 'regex:/^\d{11}$/', Rule::unique('iafas', 'ruc')],

            'direccion' => ['nullable', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'pagina_web' => ['nullable', 'string', 'max:200'],

            'fecha_inicio_cobertura' => ['required', 'date'],
            'fecha_fin_cobertura' => ['required', 'date', 'after_or_equal:fecha_inicio_cobertura'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código de la IAFAS lo genera el sistema; no lo envíes manualmente.',
            'tipo_iafa_id.required' => 'Selecciona el tipo de IAFAS.',
            'tipo_iafa_id.integer' => 'Selecciona un tipo de IAFAS válido.',
            'tipo_iafa_id.exists' => 'El tipo de IAFAS seleccionado no existe o está inactivo.',
            'razon_social.required' => 'Ingresa la razón social de la IAFAS.',
            'razon_social.string' => 'La razón social de la IAFAS debe ser texto.',
            'razon_social.max' => 'La razón social de la IAFAS no debe superar 255 caracteres.',
            'descripcion_corta.required' => 'Ingresa la descripción corta de la IAFAS.',
            'descripcion_corta.string' => 'La descripción corta de la IAFAS debe ser texto.',
            'descripcion_corta.max' => 'La descripción corta de la IAFAS no debe superar 120 caracteres.',
            'ruc.required' => 'Ingresa el RUC de la IAFAS.',
            'ruc.regex' => 'El RUC de la IAFAS debe tener 11 dígitos numéricos.',
            'ruc.unique' => 'Ya existe una IAFAS registrada con este RUC.',
            'direccion.max' => 'La dirección de la IAFAS no debe superar 255 caracteres.',
            'representante_legal.max' => 'El representante legal de la IAFAS no debe superar 150 caracteres.',
            'telefono.max' => 'El teléfono de la IAFAS no debe superar 30 caracteres.',
            'pagina_web.max' => 'La página web de la IAFAS no debe superar 200 caracteres.',
            'fecha_inicio_cobertura.required' => 'Ingresa la fecha de inicio de cobertura de la IAFAS.',
            'fecha_inicio_cobertura.date' => 'La fecha de inicio de cobertura de la IAFAS no tiene un formato válido.',
            'fecha_fin_cobertura.required' => 'Ingresa la fecha de fin de cobertura de la IAFAS.',
            'fecha_fin_cobertura.date' => 'La fecha de fin de cobertura de la IAFAS no tiene un formato válido.',
            'fecha_fin_cobertura.after_or_equal' => 'La fecha de fin de cobertura no puede ser anterior a la fecha de inicio.',
            'estado.in' => 'El estado de la IAFAS debe ser ACTIVO o INACTIVO.',
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

