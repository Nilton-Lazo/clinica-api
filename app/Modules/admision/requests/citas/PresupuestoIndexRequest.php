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

    public function messages(): array

    {

        return [

            'q.string' => 'El criterio de búsqueda de presupuestos debe ser texto.',

            'q.max' => 'El criterio de búsqueda de presupuestos no debe superar 200 caracteres.',

            'vigencia_desde.date' => 'La fecha inicial de vigencia no tiene un formato válido.',

            'vigencia_hasta.date' => 'La fecha final de vigencia no tiene un formato válido.',

            'vigencia_hasta.after_or_equal' => 'La fecha final de vigencia no puede ser anterior a la fecha inicial.',

            'estado.string' => 'El estado del presupuesto debe ser texto.',

            'estado.in' => 'El estado del presupuesto no es válido.',

            'page.integer' => 'La página solicitada debe ser un número entero.',

            'page.min' => 'La página solicitada debe ser mayor o igual a 1.',

            'per_page.integer' => 'La cantidad de presupuestos por página debe ser un número entero.',

            'per_page.min' => 'La cantidad de presupuestos por página debe ser mayor o igual a 1.',

            'per_page.max' => 'La cantidad de presupuestos por página no debe superar 100.',

        ];

    }

}

