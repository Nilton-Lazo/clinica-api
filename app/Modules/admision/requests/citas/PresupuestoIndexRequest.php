<?php



namespace App\Modules\admision\requests\citas;



use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;



class PresupuestoIndexRequest extends FormRequest

{

    public function authorize(): bool

    {

        return true;

    }



    public function rules(): array

    {

        return [

            'q' => ['nullable', 'string', 'max:200'],

            'vigencia_desde' => ['nullable', 'date'],

            'vigencia_hasta' => ['nullable', 'date', 'after_or_equal:vigencia_desde'],

            'estado' => ['nullable', 'string', Rule::in(['VIGENTE', 'UTILIZADO', 'VENCIDO', 'ANULADO'])],

            'page' => ['nullable', 'integer', 'min:1'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

        ];

    }

}

