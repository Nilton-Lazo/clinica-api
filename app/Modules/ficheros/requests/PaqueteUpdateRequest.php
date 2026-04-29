<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaqueteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['prohibited'],

            'descripcion' => ['required', 'string', 'max:255'],

            'tarifa_id' => ['required', 'integer', 'min:1'],

            'precio_sin_igv' => ['required', 'numeric', 'min:0'],

            'vigencia_actual' => ['required', 'date_format:Y-m-d'],

            'dias_hospitalizacion' => ['nullable', 'integer', 'min:0', 'max:2147483647'],

            'cuenta_contabilidad' => ['nullable', 'string', 'max:255'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.prohibited' => 'El código del paquete lo genera el sistema; no lo modifiques manualmente.',
            'descripcion.required' => 'Ingresa la descripción del paquete.',
            'descripcion.string' => 'La descripción del paquete debe ser texto.',
            'descripcion.max' => 'La descripción del paquete no debe superar 255 caracteres.',
            'tarifa_id.required' => 'Selecciona la tarifa del paquete.',
            'tarifa_id.integer' => 'Selecciona una tarifa válida para el paquete.',
            'tarifa_id.min' => 'Selecciona una tarifa válida para el paquete.',
            'precio_sin_igv.required' => 'Ingresa el precio sin IGV del paquete.',
            'precio_sin_igv.numeric' => 'El precio sin IGV del paquete debe ser numérico.',
            'precio_sin_igv.min' => 'El precio sin IGV del paquete no puede ser negativo.',
            'vigencia_actual.required' => 'Ingresa la vigencia actual del paquete.',
            'vigencia_actual.date_format' => 'La vigencia actual del paquete debe tener formato YYYY-MM-DD.',
            'dias_hospitalizacion.integer' => 'Los días de hospitalización del paquete deben ser un número entero.',
            'dias_hospitalizacion.min' => 'Los días de hospitalización del paquete no pueden ser negativos.',
            'dias_hospitalizacion.max' => 'Los días de hospitalización del paquete superan el límite permitido.',
            'cuenta_contabilidad.max' => 'La cuenta contable del paquete no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del paquete.',
            'estado.in' => 'El estado del paquete debe ser ACTIVO o INACTIVO.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descripcion')) {
            $this->merge(['descripcion' => trim((string) $this->input('descripcion'))]);
        }

        $cc = $this->input('cuenta_contabilidad');
        if ($cc === null || $cc === '') {
            $this->merge(['cuenta_contabilidad' => null]);
        } elseif ($this->has('cuenta_contabilidad')) {
            $this->merge(['cuenta_contabilidad' => trim((string) $cc) !== '' ? trim((string) $cc) : null]);
        }

        $dh = $this->input('dias_hospitalizacion');
        if ($dh === null || $dh === '') {
            $this->merge(['dias_hospitalizacion' => null]);
        }

        if ($this->has('estado')) {
            $this->merge(['estado' => strtoupper(trim((string) $this->input('estado')))]);
        }
    }
}
