<?php

namespace App\Modules\admision\requests\citas;

use App\Core\reporting\ReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CitaAtencionExportRequest extends FormRequest
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
            'inline' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'Indica el formato de descarga del reporte de atención (PDF).',
            'format.string' => 'El formato del reporte de atención debe ser texto.',
            'format.in' => 'El formato solicitado no está disponible para la atención de cita. Usa PDF.',
        ];
    }
}
