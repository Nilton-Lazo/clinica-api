<?php

namespace App\Modules\admision\requests\pacientes;

use App\Core\support\EstadoCivilPaciente;
use App\Core\support\MedioInformacionPaciente;
use App\Core\support\OcupacionPaciente;
use App\Core\support\ParentescoEmergencia;
use App\Core\support\ParentescoSeguroPaciente;
use App\Core\support\RecordStatus;
use App\Core\support\SexoPaciente;
use App\Core\support\TipoDocumentoPaciente;
use App\Core\support\TipoPaciente;
use App\Core\support\TipoSangre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    private function tipoDocumento(): string
    {
        return strtoupper(trim((string)$this->input('tipo_documento')));
    }

    private function esSinDocumento(): bool
    {
        return $this->tipoDocumento() === TipoDocumentoPaciente::SIN_DOCUMENTO->value;
    }

    public function rules(): array
    {
        $isSin = $this->esSinDocumento();

        $docRules = ['nullable', 'string', 'max:20'];
        $tipo = $this->tipoDocumento();

        if (!$isSin) {
            $docRules = ['required', 'string', 'max:20'];
            if ($tipo === TipoDocumentoPaciente::DNI->value) {
                $docRules[] = 'regex:/^\d{8}$/';
            } elseif ($tipo === TipoDocumentoPaciente::RUC->value) {
                $docRules[] = 'regex:/^\d{11}$/';
            } else {
                $docRules[] = 'regex:/^[0-9A-Za-z\-]+$/';
            }
        }

        return [
            'nr' => ['prohibited'],

            'tipo_documento' => ['required', 'string', Rule::in(TipoDocumentoPaciente::values())],
            'numero_documento' => $docRules,

            'nombres' => [$isSin ? 'nullable' : 'required', 'string', 'max:120'],
            'apellido_paterno' => [$isSin ? 'nullable' : 'required', 'string', 'max:80'],
            'apellido_materno' => [$isSin ? 'nullable' : 'required', 'string', 'max:80'],

            'estado_civil' => ['nullable', 'string', Rule::in(EstadoCivilPaciente::values())],
            'sexo' => ['nullable', 'string', Rule::in(SexoPaciente::values())],
            'fecha_nacimiento' => ['nullable', 'date'],

            'nacionalidad_iso2' => ['nullable', 'string', 'size:2', Rule::exists('paises', 'iso2')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value))],
            'ubigeo_nacimiento' => ['nullable', 'string', 'size:6', Rule::exists('ubigeos', 'codigo')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value))],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ubigeo_domicilio' => ['nullable', 'string', 'size:6', Rule::exists('ubigeos', 'codigo')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value))],

            'parentesco_seguro' => ['required', 'string', Rule::in(ParentescoSeguroPaciente::values())],
            'titular_nombre' => ['required', 'string', 'max:200'],

            'celular' => ['nullable', 'string', 'max:30'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:150'],

            'medico_tratante_id' => [
                'nullable',
                'integer',
                'required_if:tipo_paciente,' . TipoPaciente::PRIVADO->value,
                Rule::exists('medicos', 'id')->where(fn($q) => $q->where('estado', RecordStatus::ACTIVO->value)),
            ],
            'tipo_sangre' => ['nullable', 'string', Rule::in(TipoSangre::values())],
            'tipo_paciente' => ['nullable', 'string', Rule::in(TipoPaciente::values())],

            'ocupacion' => ['nullable', 'string', Rule::in(OcupacionPaciente::values())],

            'medio_informacion' => ['nullable', 'string', Rule::in(MedioInformacionPaciente::values())],
            'medio_informacion_detalle' => ['nullable', 'string', 'max:255'],

            'ubicacion_archivo_hc' => ['nullable', 'string', 'max:255'],

            'estado' => ['required', 'string', Rule::in(RecordStatus::values())],

            'contacto_emergencia' => ['nullable', 'array'],
            'contacto_emergencia.nombres' => ['nullable', 'string', 'max:120'],
            'contacto_emergencia.apellido_paterno' => ['nullable', 'string', 'max:80'],
            'contacto_emergencia.apellido_materno' => ['nullable', 'string', 'max:80'],
            'contacto_emergencia.parentesco_emergencia' => ['nullable', 'string', Rule::in(ParentescoEmergencia::values())],
            'contacto_emergencia.celular' => ['nullable', 'string', 'max:30'],
            'contacto_emergencia.telefono' => ['nullable', 'string', 'max:30'],
            'contacto_emergencia.observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nr.prohibited' => 'El número de referencia del paciente no puede modificarse manualmente.',
            'tipo_documento.required' => 'Selecciona el tipo de documento del paciente.',
            'tipo_documento.string' => 'El tipo de documento del paciente debe ser texto.',
            'tipo_documento.in' => 'El tipo de documento seleccionado no es válido.',
            'numero_documento.required' => 'Ingresa el número de documento del paciente.',
            'numero_documento.string' => 'El número de documento del paciente debe ser texto.',
            'numero_documento.max' => 'El número de documento del paciente no debe superar 20 caracteres.',
            'numero_documento.regex' => 'El número de documento no cumple el formato requerido para el tipo seleccionado.',
            'nombres.required' => 'Ingresa los nombres del paciente.',
            'nombres.string' => 'Los nombres del paciente deben ser texto.',
            'nombres.max' => 'Los nombres del paciente no deben superar 120 caracteres.',
            'apellido_paterno.required' => 'Ingresa el apellido paterno del paciente.',
            'apellido_paterno.string' => 'El apellido paterno del paciente debe ser texto.',
            'apellido_paterno.max' => 'El apellido paterno del paciente no debe superar 80 caracteres.',
            'apellido_materno.required' => 'Ingresa el apellido materno del paciente.',
            'apellido_materno.string' => 'El apellido materno del paciente debe ser texto.',
            'apellido_materno.max' => 'El apellido materno del paciente no debe superar 80 caracteres.',
            'estado_civil.string' => 'El estado civil del paciente debe ser texto.',
            'estado_civil.in' => 'El estado civil seleccionado no es válido.',
            'sexo.string' => 'El sexo del paciente debe ser texto.',
            'sexo.in' => 'El sexo seleccionado no es válido.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no tiene un formato válido.',
            'nacionalidad_iso2.string' => 'La nacionalidad del paciente debe ser texto.',
            'nacionalidad_iso2.size' => 'La nacionalidad debe tener 2 caracteres.',
            'nacionalidad_iso2.exists' => 'La nacionalidad seleccionada no existe o no está activa.',
            'ubigeo_nacimiento.string' => 'El ubigeo de nacimiento debe ser texto.',
            'ubigeo_nacimiento.size' => 'El ubigeo de nacimiento debe tener 6 caracteres.',
            'ubigeo_nacimiento.exists' => 'El ubigeo de nacimiento seleccionado no existe o no está activo.',
            'direccion.string' => 'La dirección del paciente debe ser texto.',
            'direccion.max' => 'La dirección del paciente no debe superar 255 caracteres.',
            'ubigeo_domicilio.string' => 'El ubigeo de domicilio debe ser texto.',
            'ubigeo_domicilio.size' => 'El ubigeo de domicilio debe tener 6 caracteres.',
            'ubigeo_domicilio.exists' => 'El ubigeo de domicilio seleccionado no existe o no está activo.',
            'parentesco_seguro.required' => 'Selecciona la condición o parentesco del seguro.',
            'parentesco_seguro.string' => 'La condición del seguro debe ser texto.',
            'parentesco_seguro.in' => 'La condición del seguro seleccionada no es válida.',
            'titular_nombre.required' => 'Ingresa el nombre del titular del seguro.',
            'titular_nombre.string' => 'El titular del seguro debe ser texto.',
            'titular_nombre.max' => 'El titular del seguro no debe superar 200 caracteres.',
            'celular.string' => 'El celular del paciente debe ser texto.',
            'celular.max' => 'El celular del paciente no debe superar 30 caracteres.',
            'telefono.string' => 'El teléfono del paciente debe ser texto.',
            'telefono.max' => 'El teléfono del paciente no debe superar 30 caracteres.',
            'email.string' => 'El correo electrónico del paciente debe ser texto.',
            'email.max' => 'El correo electrónico del paciente no debe superar 150 caracteres.',
            'medico_tratante_id.integer' => 'El médico tratante seleccionado no es válido.',
            'medico_tratante_id.required_if' => 'Selecciona el médico tratante cuando el tipo de paciente es Privado.',
            'medico_tratante_id.exists' => 'El médico tratante seleccionado no existe o no está activo.',
            'tipo_sangre.string' => 'El tipo de sangre debe ser texto.',
            'tipo_sangre.in' => 'El tipo de sangre seleccionado no es válido.',
            'tipo_paciente.string' => 'El tipo de paciente debe ser texto.',
            'tipo_paciente.in' => 'El tipo de paciente seleccionado no es válido.',
            'ocupacion.string' => 'La ocupación del paciente debe ser texto.',
            'ocupacion.in' => 'La ocupación seleccionada no es válida.',
            'medio_informacion.string' => 'El medio de información debe ser texto.',
            'medio_informacion.in' => 'El medio de información seleccionado no es válido.',
            'medio_informacion_detalle.string' => 'El detalle del medio de información debe ser texto.',
            'medio_informacion_detalle.max' => 'El detalle del medio de información no debe superar 255 caracteres.',
            'ubicacion_archivo_hc.string' => 'La ubicación del archivo de historia clínica debe ser texto.',
            'ubicacion_archivo_hc.max' => 'La ubicación del archivo de historia clínica no debe superar 255 caracteres.',
            'estado.required' => 'Selecciona el estado del paciente.',
            'estado.string' => 'El estado del paciente debe ser texto.',
            'estado.in' => 'El estado del paciente no es válido.',
            'contacto_emergencia.array' => 'El contacto de emergencia tiene un formato inválido.',
            'contacto_emergencia.nombres.string' => 'Los nombres del contacto de emergencia deben ser texto.',
            'contacto_emergencia.nombres.max' => 'Los nombres del contacto de emergencia no deben superar 120 caracteres.',
            'contacto_emergencia.apellido_paterno.string' => 'El apellido paterno del contacto de emergencia debe ser texto.',
            'contacto_emergencia.apellido_paterno.max' => 'El apellido paterno del contacto de emergencia no debe superar 80 caracteres.',
            'contacto_emergencia.apellido_materno.string' => 'El apellido materno del contacto de emergencia debe ser texto.',
            'contacto_emergencia.apellido_materno.max' => 'El apellido materno del contacto de emergencia no debe superar 80 caracteres.',
            'contacto_emergencia.parentesco_emergencia.string' => 'El parentesco del contacto de emergencia debe ser texto.',
            'contacto_emergencia.parentesco_emergencia.in' => 'El parentesco del contacto de emergencia no es válido.',
            'contacto_emergencia.celular.string' => 'El celular del contacto de emergencia debe ser texto.',
            'contacto_emergencia.celular.max' => 'El celular del contacto de emergencia no debe superar 30 caracteres.',
            'contacto_emergencia.telefono.string' => 'El teléfono del contacto de emergencia debe ser texto.',
            'contacto_emergencia.telefono.max' => 'El teléfono del contacto de emergencia no debe superar 30 caracteres.',
            'contacto_emergencia.observaciones.string' => 'Las observaciones del contacto de emergencia deben ser texto.',
            'contacto_emergencia.observaciones.max' => 'Las observaciones del contacto de emergencia no deben superar 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_documento' => $this->has('tipo_documento') ? strtoupper(trim((string)$this->input('tipo_documento'))) : null,
            'numero_documento' => $this->has('numero_documento') ? preg_replace('/\s+/', '', trim((string)$this->input('numero_documento'))) : null,
            'nombres' => $this->has('nombres') ? trim((string)$this->input('nombres')) : null,
            'apellido_paterno' => $this->has('apellido_paterno') ? trim((string)$this->input('apellido_paterno')) : null,
            'apellido_materno' => $this->has('apellido_materno') ? trim((string)$this->input('apellido_materno')) : null,
            'direccion' => $this->has('direccion') ? trim((string)$this->input('direccion')) : null,
            'titular_nombre' => $this->has('titular_nombre') ? trim((string)$this->input('titular_nombre')) : null,
            'celular' => $this->has('celular') ? trim((string)$this->input('celular')) : null,
            'telefono' => $this->has('telefono') ? trim((string)$this->input('telefono')) : null,
            'email' => $this->has('email') ? trim((string)$this->input('email')) : null,
            'medio_informacion_detalle' => $this->has('medio_informacion_detalle') ? trim((string)$this->input('medio_informacion_detalle')) : null,
            'ubicacion_archivo_hc' => $this->has('ubicacion_archivo_hc') ? trim((string)$this->input('ubicacion_archivo_hc')) : null,
        ]);

        foreach (['estado_civil', 'sexo', 'parentesco_seguro', 'ocupacion', 'medio_informacion', 'tipo_paciente', 'estado'] as $k) {
            if ($this->has($k) && $this->input($k) !== null && $this->input($k) !== '') {
                $this->merge([$k => strtoupper(trim((string)$this->input($k)))]);
            }
        }

        if ($this->has('nacionalidad_iso2') && $this->input('nacionalidad_iso2') !== null && $this->input('nacionalidad_iso2') !== '') {
            $this->merge(['nacionalidad_iso2' => strtoupper(trim((string)$this->input('nacionalidad_iso2')))]);
        }

        if ($this->has('tipo_sangre') && $this->input('tipo_sangre') !== null && $this->input('tipo_sangre') !== '') {
            $this->merge(['tipo_sangre' => strtoupper(trim((string)$this->input('tipo_sangre')))]);
        }

        if ($this->has('contacto_emergencia') && is_array($this->input('contacto_emergencia'))) {
            $ce = $this->input('contacto_emergencia');
            foreach (['nombres', 'apellido_paterno', 'apellido_materno', 'celular', 'telefono', 'observaciones'] as $k) {
                if (array_key_exists($k, $ce)) {
                    $v = trim((string)$ce[$k]);
                    $ce[$k] = $v !== '' ? $v : null;
                }
            }
            if (array_key_exists('parentesco_emergencia', $ce) && $ce['parentesco_emergencia'] !== null && $ce['parentesco_emergencia'] !== '') {
                $ce['parentesco_emergencia'] = strtoupper(trim((string)$ce['parentesco_emergencia']));
            }
            $this->merge(['contacto_emergencia' => $ce]);
        }
    }
}
