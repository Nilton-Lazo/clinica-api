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

class PacienteStoreRequest extends FormRequest
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

            'medico_tratante_id' => ['nullable', 'integer'],
            'tipo_sangre' => ['nullable', 'string', Rule::in(TipoSangre::values())],
            'tipo_paciente' => ['nullable', 'string', Rule::in(TipoPaciente::values())],

            'ocupacion' => ['nullable', 'string', Rule::in(OcupacionPaciente::values())],

            'medio_informacion' => ['nullable', 'string', Rule::in(MedioInformacionPaciente::values())],
            'medio_informacion_detalle' => ['nullable', 'string', 'max:255'],

            'ubicacion_archivo_hc' => ['nullable', 'string', 'max:255'],

            'estado' => ['sometimes', 'string', Rule::in(RecordStatus::values())],

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

        foreach (['estado_civil', 'sexo', 'parentesco_seguro', 'ocupacion', 'medio_informacion', 'tipo_paciente'] as $k) {
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

        if (!$this->has('estado') || $this->input('estado') === null || $this->input('estado') === '') {
            $this->merge(['estado' => RecordStatus::ACTIVO->value]);
        } else {
            $this->merge(['estado' => strtoupper(trim((string)$this->input('estado')))]);
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
