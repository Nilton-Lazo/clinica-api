<?php

namespace App\Modules\admision\requests\ficheros;

use App\Core\support\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EspecialidadUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('especialidad')?->id ?? $this->route('especialidad');

        return [
            'codigo' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z\.\-_]+$/', Rule::unique('especialidades', 'codigo')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],
        ];
    }
}
