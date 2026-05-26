<?php

namespace App\Modules\admision\services\citas;

use App\Core\reporting\ReportDisplayFormatter;
use App\Core\reporting\ReportFilename;
use App\Core\reporting\ReportFormat;
use App\Core\reporting\ReportGenerationContext;
use App\Core\support\EstadoFacturacionServicio;
use App\Models\User;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\reports\AtencionCitaViewData;
use Illuminate\Validation\ValidationException;

class AtencionCitaReportService
{
    public const MODULO_LABEL = 'Admisión — Atención de cita';

    private const CATEGORIA_CONSULTAS_MEDICAS = '50';

    public function buildViewData(AgendaCita $cita, User $actor): AtencionCitaViewData
    {
        $c = $this->loadForReport($cita);
        $atencion = $c->atencion;

        if (! $atencion) {
            throw ValidationException::withMessages([
                'cita' => ['La atención de la cita aún no ha sido guardada. Guarda la atención antes de imprimir el reporte.'],
            ]);
        }

        $nroCuenta = trim((string) ($atencion->nro_cuenta ?? $c->cuenta ?? ''));
        if ($nroCuenta === '') {
            throw ValidationException::withMessages([
                'cuenta' => ['La atención aún no tiene número de cuenta generado. Guarda la atención antes de imprimir el reporte.'],
            ]);
        }

        $paciente = $c->paciente;
        $programacion = $c->programacion;
        $plan = $atencion->pacientePlan;
        $tipoCliente = $plan?->tipoCliente;
        $tarifa = $atencion->tarifa ?? $tipoCliente?->tarifa;
        $esPrecioDirecto = (bool) ($tarifa?->es_precio_directo ?? false);
        $cuenta = Cuenta::query()
            ->where('origen', 'CITA_ATENCION')
            ->where('origen_id', (int) $atencion->id)
            ->first();

        $servicios = [];
        $totalSinIgv = 0.0;
        $totalIgv = 0.0;
        $totalConIgv = 0.0;
        $showDescuento = false;
        $showAumento = false;

        foreach ($atencion->servicios as $servicio) {
            $tarifaServicio = $servicio->tarifaServicio;
            $cantidad = max(1, (float) $servicio->cantidad);
            $descuentoPct = (float) $servicio->descuento_pct;
            $aumentoPct = (float) $servicio->aumento_pct;
            $precioSinIgv = (float) $servicio->precio_sin_igv;
            $precioConIgv = (float) $servicio->precio_con_igv;
            $igv = max(0, $precioConIgv - $precioSinIgv);
            $totalSinIgv += $precioSinIgv;
            $totalIgv += $igv;
            $totalConIgv += $precioConIgv;
            $showDescuento = $showDescuento || $descuentoPct > 0;
            $showAumento = $showAumento || $aumentoPct > 0;
            $detalleFinanciero = $this->detalleFinanciero($servicio, $esPrecioDirecto);

            $servicios[] = [
                'codigo' => ReportDisplayFormatter::text($tarifaServicio?->codigo),
                'categoria' => ReportDisplayFormatter::text($tarifaServicio?->categoria?->codigo),
                'descripcion' => ReportDisplayFormatter::text($tarifaServicio?->descripcion),
                'cantidad' => $this->formatNumber($cantidad),
                'precio_unitario_con_igv' => $this->formatMoney($precioConIgv / $cantidad),
                'importe_con_igv' => $this->formatMoney($precioConIgv),
                'cop_fijo' => (float) $servicio->cop_fijo > 0 ? $this->formatMoney((float) $servicio->cop_fijo) : '—',
                'cop_var' => (float) $servicio->cop_var > 0 ? $this->formatNumber((float) $servicio->cop_var).'%' : '—',
                'descuento' => $descuentoPct > 0 ? $this->formatNumber($descuentoPct).'%' : '—',
                'aumento' => $aumentoPct > 0 ? $this->formatNumber($aumentoPct).'%' : '—',
                'precio_sin_igv' => $this->formatMoney($precioSinIgv),
                'igv' => $this->formatMoney($igv),
                'precio_con_igv' => $this->formatMoney($precioConIgv),
                'pago_paciente' => $detalleFinanciero['pago_paciente'],
                'pago_aseguradora' => $detalleFinanciero['pago_aseguradora'],
                'estado' => ReportDisplayFormatter::enumLabel(
                    in_array((string) $servicio->estado_facturacion, EstadoFacturacionServicio::values(), true)
                        ? (string) $servicio->estado_facturacion
                        : EstadoFacturacionServicio::PENDIENTE->value
                ),
            ];
        }

        return new AtencionCitaViewData(
            nroCuenta: $nroCuenta,
            codigoCita: ReportDisplayFormatter::text($c->codigo),
            fechaCita: $c->fecha ? $c->fecha->format('d/m/Y') : '—',
            fechaAtencion: $atencion->updated_at ? $atencion->updated_at->format('d/m/Y') : ($c->fecha ? $c->fecha->format('d/m/Y') : '—'),
            horaCita: $this->formatHora($c->hora),
            horaAtencion: $this->formatHora($atencion->hora_asistencia),
            motivo: ReportDisplayFormatter::text($c->motivo),
            paciente: $paciente ? ReportDisplayFormatter::fullName($paciente->apellido_paterno, $paciente->apellido_materno, $paciente->nombres) : ReportDisplayFormatter::text($c->paciente_nombre),
            documento: $this->formatDocumento($paciente),
            firmaPaciente: $this->formatFirmaPaciente($paciente, $c->paciente_nombre),
            numeroHistoriaClinica: ReportDisplayFormatter::text($paciente?->hc ?? $c->hc),
            numeroReferencia: ReportDisplayFormatter::text($paciente?->nr ?? $c->nr),
            edad: $paciente?->edad !== null ? (string) $paciente->edad.' años' : ReportDisplayFormatter::text($c->edad !== null ? (string) $c->edad : null),
            sexo: ReportDisplayFormatter::text($paciente?->sexo ?? $c->sexo),
            telefono: $paciente ? ReportDisplayFormatter::joinPhone($paciente->celular, $paciente->telefono) : '—',
            especialidad: ReportDisplayFormatter::text($programacion?->especialidad?->descripcion),
            medicoProgramado: $this->formatMedico($programacion?->medico),
            consultorio: $this->formatConsultorio($programacion?->consultorio),
            contratante: $this->formatContratante($tipoCliente?->contratante),
            iafa: $this->formatIafa($c),
            plan: ReportDisplayFormatter::text($tipoCliente?->descripcion_tipo_cliente),
            tarifa: ReportDisplayFormatter::text($tarifa?->descripcion_tarifa),
            esPrecioDirecto: $esPrecioDirecto,
            parentesco: ReportDisplayFormatter::enumLabel($atencion->parentesco_seguro),
            titular: ReportDisplayFormatter::text($atencion->titular_nombre),
            indicadores: $this->formatIndicadores($atencion),
            soat: $this->formatSoat($atencion),
            showDescuento: $showDescuento,
            showAumento: $showAumento,
            servicios: $servicios,
            totalSinIgv: $this->formatMoney($totalSinIgv),
            totalIgv: $this->formatMoney($totalIgv),
            totalConIgv: $this->formatMoney($totalConIgv),
            montoAPagar: $this->formatMoney((float) ($atencion->monto_a_pagar ?? $totalConIgv)),
            usuario: $this->formatUsuario($actor),
            modulo: self::MODULO_LABEL.($cuenta && $cuenta->estado ? ' · Cuenta '.$cuenta->estado : ''),
        );
    }

    public function generationContext(User $actor): ReportGenerationContext
    {
        return ReportGenerationContext::forUser($actor);
    }

    public function filenameForExport(AtencionCitaViewData $report, ReportFormat $format): string
    {
        return ReportFilename::buildStructured(
            modulo: 'admision',
            tipoReporte: 'atencion_cita',
            entidad: 'paciente',
            identificador: $this->filenameIdentifier($report),
            format: $format,
        );
    }

    private function filenameIdentifier(AtencionCitaViewData $report): ?string
    {
        $hc = trim($report->numeroHistoriaClinica);
        if ($hc !== '' && $hc !== '—') {
            return $hc;
        }

        $documento = trim($report->documento);
        if ($documento !== '' && $documento !== '—') {
            return $documento;
        }

        $cuenta = trim($report->nroCuenta);
        return $cuenta !== '' && $cuenta !== '—' ? $cuenta : null;
    }

    private function loadForReport(AgendaCita $cita): AgendaCita
    {
        return AgendaCita::query()
            ->with([
                'programacion.especialidad:id,codigo,descripcion',
                'programacion.medico:id,codigo,nombres,apellido_paterno,apellido_materno',
                'programacion.consultorio:id,abreviatura,descripcion',
                'paciente:id,tipo_documento,numero_documento,nr,nombres,apellido_paterno,apellido_materno,sexo,fecha_nacimiento,edad,celular,telefono',
                'iafa:id,codigo,descripcion_corta,razon_social',
                'atencion.pacientePlan.tipoCliente:id,codigo,descripcion_tipo_cliente,tarifa_id,iafa_id,contratante_id',
                'atencion.pacientePlan.tipoCliente.contratante:id,codigo,razon_social,ruc',
                'atencion.pacientePlan.tipoCliente.tarifa:id,codigo,descripcion_tarifa,es_precio_directo',
                'atencion.tarifa:id,codigo,descripcion_tarifa,es_precio_directo',
                'atencion.servicios.tarifaServicio:id,codigo,descripcion,categoria_id',
                'atencion.servicios.tarifaServicio.categoria:id,codigo',
            ])
            ->findOrFail($cita->id);
    }

    private function formatDocumento($paciente): string
    {
        if (! $paciente) {
            return '—';
        }

        $tipo = trim((string) ($paciente->tipo_documento ?? ''));
        $numero = trim((string) ($paciente->numero_documento ?? ''));

        if ($tipo !== '' && $numero !== '') {
            return $tipo.' '.$numero;
        }

        return ReportDisplayFormatter::text($numero);
    }

    private function formatFirmaPaciente($paciente, ?string $fallbackName): string
    {
        $nombre = $paciente
            ? ReportDisplayFormatter::fullName($paciente->apellido_paterno, $paciente->apellido_materno, $paciente->nombres)
            : ReportDisplayFormatter::text($fallbackName);
        $documento = trim((string) ($paciente?->numero_documento ?? ''));

        if ($documento !== '' && $nombre !== '—') {
            return $nombre.' - '.$documento;
        }

        return $nombre;
    }

    private function formatMedico($medico): string
    {
        if (! $medico) {
            return '—';
        }

        $nombre = ReportDisplayFormatter::fullName($medico->apellido_paterno ?? null, $medico->apellido_materno ?? null, $medico->nombres ?? null);

        return $nombre;
    }

    private function formatConsultorio($consultorio): string
    {
        if (! $consultorio) {
            return '—';
        }

        $descripcion = trim((string) ($consultorio->descripcion ?? ''));

        return ReportDisplayFormatter::text($descripcion);
    }

    private function formatContratante($contratante): string
    {
        if (! $contratante) {
            return '—';
        }

        $nombre = trim((string) ($contratante->razon_social ?? ''));
        return ReportDisplayFormatter::text($nombre);
    }

    private function formatIafa(AgendaCita $cita): string
    {
        $iafa = $cita->iafa;
        if (! $iafa) {
            return '—';
        }

        $nombre = trim((string) ($iafa->descripcion_corta ?? $iafa->razon_social ?? ''));
        return ReportDisplayFormatter::text($nombre);
    }

    private function formatIndicadores($atencion): string
    {
        $items = [];
        if ($atencion->control_pre_post_natal) {
            $items[] = 'Control pre/post natal';
        }
        if ($atencion->control_nino_sano) {
            $items[] = 'Control niño sano';
        }
        if ($atencion->chequeo) {
            $items[] = 'Chequeo';
        }
        if ($atencion->carencia) {
            $items[] = 'Carencia';
        }
        if ($atencion->latencia) {
            $items[] = 'Latencia';
        }

        return $items !== [] ? implode(', ', $items) : '—';
    }

    private function formatSoat($atencion): string
    {
        if (! $atencion->soat_activo) {
            return 'No aplica';
        }

        $parts = array_values(array_filter([
            trim((string) ($atencion->soat_numero_poliza ?? '')) !== '' ? 'Póliza '.$atencion->soat_numero_poliza : null,
            trim((string) ($atencion->soat_numero_placa ?? '')) !== '' ? 'Placa '.$atencion->soat_numero_placa : null,
        ]));

        return $parts !== [] ? implode(' · ', $parts) : 'SOAT activo';
    }

    private function formatHora($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof \Carbon\Carbon) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }

    private function detalleFinanciero($servicio, bool $esPrecioDirecto): array
    {
        if ($esPrecioDirecto) {
            return [
                'pago_paciente' => $this->formatMoney((float) $servicio->precio_con_igv),
                'pago_aseguradora' => '—',
            ];
        }

        $cantidad = max(1, (float) $servicio->cantidad);
        $importeConIgv = (float) $servicio->precio_con_igv;
        $categoria = trim((string) ($servicio->tarifaServicio?->categoria?->codigo ?? ''));
        $copFijo = (float) $servicio->cop_fijo;
        $copVar = (float) $servicio->cop_var;

        if ($categoria === self::CATEGORIA_CONSULTAS_MEDICAS) {
            $copagoFijo = $copFijo > 0 ? round($copFijo * $cantidad, 4) : null;
            $pagoAseguradora = $copagoFijo !== null ? round($importeConIgv - $copagoFijo, 4) : null;

            return [
                'pago_paciente' => $copagoFijo !== null ? $this->formatMoney($copagoFijo) : '—',
                'pago_aseguradora' => $pagoAseguradora !== null ? $this->formatMoney($pagoAseguradora) : '—',
            ];
        }

        $copagoVariable = round($importeConIgv * (1 - $copVar / 100), 4);
        $pagoAseguradora = round($importeConIgv * ($copVar / 100), 4);

        return [
            'pago_paciente' => $this->formatMoney($copagoVariable),
            'pago_aseguradora' => $this->formatMoney($pagoAseguradora),
        ];
    }

    private function formatMoney(float $value): string
    {
        return 'S/ '.number_format($value, 2, '.', ',');
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
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
