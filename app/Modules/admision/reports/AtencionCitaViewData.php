<?php

namespace App\Modules\admision\reports;

use App\Core\reporting\Contracts\ReportViewData;

final class AtencionCitaViewData implements ReportViewData
{
    public function __construct(
        public readonly string $nroCuenta,
        public readonly string $codigoCita,
        public readonly string $fechaCita,
        public readonly string $fechaAtencion,
        public readonly string $horaCita,
        public readonly string $horaAtencion,
        public readonly string $motivo,
        public readonly string $paciente,
        public readonly string $documento,
        public readonly string $firmaPaciente,
        public readonly string $numeroHistoriaClinica,
        public readonly string $numeroReferencia,
        public readonly string $edad,
        public readonly string $sexo,
        public readonly string $telefono,
        public readonly string $especialidad,
        public readonly string $medicoProgramado,
        public readonly string $consultorio,
        public readonly string $contratante,
        public readonly string $iafa,
        public readonly string $plan,
        public readonly string $tarifa,
        public readonly bool $esPrecioDirecto,
        public readonly string $parentesco,
        public readonly string $titular,
        public readonly string $indicadores,
        public readonly string $soat,
        public readonly bool $showDescuento,
        public readonly bool $showAumento,
        public readonly array $servicios,
        public readonly string $totalSinIgv,
        public readonly string $totalIgv,
        public readonly string $totalConIgv,
        public readonly string $montoAPagar,
        public readonly string $usuario,
        public readonly string $modulo,
    ) {}

    public function reportKey(): string
    {
        return 'admision.atencion-cita';
    }

    public function reportTitle(): string
    {
        return 'ORDEN DE ATENCIÓN AMBULATORIA';
    }

    public function toArray(): array
    {
        return [
            'nro_cuenta' => $this->nroCuenta,
            'codigo_cita' => $this->codigoCita,
            'fecha_cita' => $this->fechaCita,
            'fecha_atencion' => $this->fechaAtencion,
            'hora_cita' => $this->horaCita,
            'hora_atencion' => $this->horaAtencion,
            'motivo' => $this->motivo,
            'paciente' => $this->paciente,
            'documento' => $this->documento,
            'firma_paciente' => $this->firmaPaciente,
            'numero_historia_clinica' => $this->numeroHistoriaClinica,
            'numero_referencia' => $this->numeroReferencia,
            'edad' => $this->edad,
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'especialidad' => $this->especialidad,
            'medico_programado' => $this->medicoProgramado,
            'consultorio' => $this->consultorio,
            'contratante' => $this->contratante,
            'iafa' => $this->iafa,
            'plan' => $this->plan,
            'tarifa' => $this->tarifa,
            'es_precio_directo' => $this->esPrecioDirecto,
            'parentesco' => $this->parentesco,
            'titular' => $this->titular,
            'indicadores' => $this->indicadores,
            'soat' => $this->soat,
            'show_descuento' => $this->showDescuento,
            'show_aumento' => $this->showAumento,
            'servicios' => $this->servicios,
            'total_sin_igv' => $this->totalSinIgv,
            'total_igv' => $this->totalIgv,
            'total_con_igv' => $this->totalConIgv,
            'monto_a_pagar' => $this->montoAPagar,
            'usuario' => $this->usuario,
            'modulo' => $this->modulo,
        ];
    }
}
