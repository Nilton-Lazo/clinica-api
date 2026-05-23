<?php

namespace App\Modules\admision\requests\pacientes;

use App\Core\reporting\ReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteFiliacionExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in([ReportFormat::Pdf->value])],
            'preview' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Indica el formato de descarga del reporte (PDF).',
            'format.string' => 'El formato del reporte debe ser texto.',
            'format.in' => 'El formato solicitado no está disponible para la hoja de filiación. Usa PDF.',
        ];
    }
}
