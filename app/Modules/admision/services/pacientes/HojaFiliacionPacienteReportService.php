<?php

namespace App\Modules\admision\services\pacientes;

use App\Core\reporting\ReportDisplayFormatter;
use App\Core\reporting\ReportFilename;
use App\Core\reporting\ReportFormat;
use App\Core\reporting\ReportGenerationContext;
use App\Core\support\SexoPaciente;
use App\Models\User;
use App\Modules\admision\models\Medico;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\PacienteContactoEmergencia;
use App\Modules\admision\models\Pais;
use App\Modules\admision\models\Ubigeo;
use App\Modules\admision\reports\HojaFiliacionPacienteViewData;
use Illuminate\Validation\ValidationException;

class HojaFiliacionPacienteReportService
{
    public const MODULO_LABEL = 'Admisión — Historia clínica';

    public function __construct(
        private PacienteService $pacientes,
    ) {}

    public function buildViewData(Paciente $paciente, User $actor): HojaFiliacionPacienteViewData
    {
        $p = $this->pacientes->loadForFiliacionReport($paciente);
        $tz = (string) config('app.timezone', 'UTC');
        $created = $p->created_at?->copy()->setTimezone($tz);

        $contacto = $p->contactoEmergencia instanceof PacienteContactoEmergencia
            ? $p->contactoEmergencia
            : null;

        return new HojaFiliacionPacienteViewData(
            numeroHistoriaClinica: ReportDisplayFormatter::text($p->hc !== '' ? $p->hc : null),
            numeroReferencia: ReportDisplayFormatter::text($p->nr),
            apellidoPaterno: ReportDisplayFormatter::text($p->apellido_paterno),
            apellidoMaterno: ReportDisplayFormatter::text($p->apellido_materno),
            nombresCompletos: ReportDisplayFormatter::text($p->nombres),
            domicilioActual: $this->formatDomicilio($p),
            telefono: ReportDisplayFormatter::joinPhone($p->celular, $p->telefono),
            ocupacion: ReportDisplayFormatter::enumLabel($p->ocupacion),
            fechaNacimiento: $p->fecha_nacimiento
                ? $p->fecha_nacimiento->format('d/m/Y')
                : '—',
            estadoCivil: ReportDisplayFormatter::enumLabel($p->estado_civil),
            edad: $p->edad !== null ? (string) $p->edad.' años' : '—',
            lugarNacimiento: $this->formatLugarNacimiento($p),
            nacionalidad: $this->formatNacionalidad($p),
            sexo: ReportDisplayFormatter::text(SexoPaciente::formatForDisplay($p->sexo)),
            parentescoConPaciente: ReportDisplayFormatter::enumLabel($p->parentesco_seguro),
            familiarResponsable: $this->formatFamiliarResponsable($contacto),
            telefonoFamiliar: $contacto
                ? ReportDisplayFormatter::joinPhone($contacto->celular, $contacto->telefono)
                : '—',
            medicoTratante: $this->formatMedicoTratante($p),
            fechaAdmision: $created ? $created->format('d/m/Y') : '—',
            horaAdmision: $created ? $created->format('H:i') : '—',
            usuario: $this->formatUsuario($actor),
            modulo: self::MODULO_LABEL,
        );
    }

    public function generationContext(User $actor): ReportGenerationContext
    {
        return ReportGenerationContext::forUser($actor);
    }

    public function filenameForExport(HojaFiliacionPacienteViewData $report, Paciente $paciente, ReportFormat $format): string
    {
        return ReportFilename::buildStructured(
            modulo: 'admision',
            tipoReporte: 'hoja_filiacion',
            entidad: 'paciente',
            identificador: $this->filenameIdentifier($report, $paciente),
            format: $format,
        );
    }

    private function filenameIdentifier(HojaFiliacionPacienteViewData $report, Paciente $paciente): ?string
    {
        $hc = trim($report->numeroHistoriaClinica);
        if ($hc !== '' && $hc !== '—') {
            return $hc;
        }

        $nr = trim($report->numeroReferencia);
        if ($nr !== '' && $nr !== '—') {
            return $nr;
        }

        $key = $paciente->getKey();

        return $key !== null ? (string) $key : null;
    }

    public function resolvePacienteOrFail(int $pacienteId): Paciente
    {
        $p = Paciente::query()->whereKey($pacienteId)->first();
        if (! $p) {
            throw ValidationException::withMessages([
                'paciente_id' => ['El paciente seleccionado no existe. Actualiza la lista e inténtalo de nuevo.'],
            ]);
        }

        return $p;
    }

    private function formatDomicilio(Paciente $p): string
    {
        $dir = trim((string) ($p->direccion ?? ''));
        $ubigeoLabel = $this->ubigeoLabel($p->ubigeoDomicilio);
        if ($dir !== '' && $ubigeoLabel !== '') {
            return $dir.' — '.$ubigeoLabel;
        }
        if ($dir !== '') {
            return $dir;
        }
        if ($ubigeoLabel !== '') {
            return $ubigeoLabel;
        }

        return '—';
    }

    private function formatLugarNacimiento(Paciente $p): string
    {
        $ubigeoLabel = $this->ubigeoLabel($p->ubigeoNacimiento);
        if ($ubigeoLabel !== '') {
            return $ubigeoLabel;
        }

        return '—';
    }

    private function formatNacionalidad(Paciente $p): string
    {
        $pais = $p->paisNacionalidad;
        if ($pais instanceof Pais) {
            $nombre = trim((string) ($pais->nombre ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        $iso = trim((string) ($p->nacionalidad_iso2 ?? ''));

        return $iso !== '' ? $iso : '—';
    }

    private function ubigeoLabel(?Ubigeo $ubigeo): string
    {
        if (! $ubigeo) {
            return '';
        }
        $parts = array_values(array_filter([
            trim((string) ($ubigeo->distrito ?? '')),
            trim((string) ($ubigeo->provincia ?? '')),
            trim((string) ($ubigeo->departamento ?? '')),
        ], fn ($x) => $x !== ''));

        return $parts !== [] ? implode(', ', $parts) : trim((string) ($ubigeo->codigo ?? ''));
    }

    private function formatFamiliarResponsable(?PacienteContactoEmergencia $contacto): string
    {
        if (! $contacto) {
            return '—';
        }

        return ReportDisplayFormatter::fullName(
            $contacto->apellido_paterno,
            $contacto->apellido_materno,
            $contacto->nombres
        );
    }

    private function formatMedicoTratante(Paciente $p): string
    {
        $medico = $p->medicoTratante;
        if (! $medico instanceof Medico) {
            return '—';
        }

        $nom = trim((string) ($medico->nombre_completo ?? ''));
        if ($nom !== '') {
            return $nom;
        }

        return ReportDisplayFormatter::fullName(
            $medico->apellido_paterno ?? null,
            $medico->apellido_materno ?? null,
            $medico->nombres ?? null
        );
    }

    private function formatUsuario(User $actor): string
    {
        $login = trim((string) ($actor->username ?? ''));
        if ($login !== '') {
            return $login;
        }

        return (string) $actor->id;
    }
}
