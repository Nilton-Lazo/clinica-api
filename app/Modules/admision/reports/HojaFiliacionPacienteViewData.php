<?php

namespace App\Modules\admision\reports;

use App\Core\reporting\Contracts\ReportViewData;

final class HojaFiliacionPacienteViewData implements ReportViewData
{
    public function __construct(
        public readonly string $numeroHistoriaClinica,
        public readonly string $numeroReferencia,
        public readonly string $apellidoPaterno,
        public readonly string $apellidoMaterno,
        public readonly string $nombresCompletos,
        public readonly string $domicilioActual,
        public readonly string $telefono,
        public readonly string $ocupacion,
        public readonly string $fechaNacimiento,
        public readonly string $estadoCivil,
        public readonly string $edad,
        public readonly string $lugarNacimiento,
        public readonly string $nacionalidad,
        public readonly string $sexo,
        public readonly string $parentescoConPaciente,
        public readonly string $familiarResponsable,
        public readonly string $telefonoFamiliar,
        public readonly string $medicoTratante,
        public readonly string $fechaAdmision,
        public readonly string $horaAdmision,
        public readonly string $usuario,
        public readonly string $modulo,
    ) {}

    public function reportKey(): string
    {
        return 'admision.hoja-filiacion-paciente';
    }

    public function reportTitle(): string
    {
        return 'HOJA DE FILIACIÓN DEL PACIENTE';
    }

    public function toArray(): array
    {
        return [
            'numero_historia_clinica' => $this->numeroHistoriaClinica,
            'numero_referencia' => $this->numeroReferencia,
            'apellido_paterno' => $this->apellidoPaterno,
            'apellido_materno' => $this->apellidoMaterno,
            'nombres_completos' => $this->nombresCompletos,
            'domicilio_actual' => $this->domicilioActual,
            'telefono' => $this->telefono,
            'ocupacion' => $this->ocupacion,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'estado_civil' => $this->estadoCivil,
            'edad' => $this->edad,
            'lugar_nacimiento' => $this->lugarNacimiento,
            'nacionalidad' => $this->nacionalidad,
            'sexo' => $this->sexo,
            'parentesco_con_paciente' => $this->parentescoConPaciente,
            'familiar_responsable' => $this->familiarResponsable,
            'telefono_familiar' => $this->telefonoFamiliar,
            'medico_tratante' => $this->medicoTratante,
            'fecha_admision' => $this->fechaAdmision,
            'hora_admision' => $this->horaAdmision,
            'usuario' => $this->usuario,
            'modulo' => $this->modulo,
        ];
    }
}
