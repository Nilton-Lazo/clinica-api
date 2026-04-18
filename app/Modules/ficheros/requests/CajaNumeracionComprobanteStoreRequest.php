<?php

namespace App\Modules\ficheros\requests;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CajaNumeracionComprobanteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo_documento_id' => ['required', 'integer', Rule::exists('caja_tipos_documento', 'id')->where('estado', RecordStatus::ACTIVO->value)],
            'serie' => ['required', 'string', 'max:20'],
            'numero' => ['required', 'integer', 'min:1', 'max:9999999'],
            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $serie = strtoupper(trim((string) $this->input('serie', '')));
        if ($serie !== '') {
            $this->merge(['serie' => $serie]);
        }
        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        }
    }
}
