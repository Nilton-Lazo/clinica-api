<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use App\Modules\ficheros\support\CajaNumeracionSerie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaNumeracionComprobanteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo_documento_id' => ['required', 'integer', Rule::exists('caja_tipos_documento', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'serie' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'numero' => ['required', 'integer', 'min:1', 'max:9999999'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento_id.required' => 'Selecciona el tipo de documento para la numeración.',
            'tipo_documento_id.integer' => 'Selecciona un tipo de documento válido.',
            'tipo_documento_id.exists' => 'El tipo de documento seleccionado no existe o está inactivo.',
            'serie.required' => 'Ingresa la serie de la numeración.',
            'serie.size' => 'La serie debe tener exactamente 3 dígitos numéricos.',
            'serie.regex' => 'La serie solo puede contener dígitos (por ejemplo 004).',
            'numero.required' => 'Ingresa el número inicial de la numeración.',
            'numero.integer' => 'El número inicial debe ser un valor entero.',
            'numero.min' => 'El número inicial debe ser mayor o igual a 1.',
            'numero.max' => 'El número inicial no debe superar 9999999.',
            'estado.required' => 'Selecciona el estado de la numeración de comprobante.',
            'estado.in' => 'Selecciona un estado válido para la numeración de comprobante.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $serie = CajaNumeracionSerie::tryNormalize($this->input('serie'));
        if ($serie !== null) {
            $this->merge(['serie' => $serie]);
        }
    }
}
