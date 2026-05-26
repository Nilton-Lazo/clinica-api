@extends('reports.layouts.pdf')

@php
    $docTitle = 'Atención de cita';
    $servicesColspan = 6 + ($report->showDescuento ? 1 : 0) + ($report->showAumento ? 1 : 0) + ($report->esPrecioDirecto ? 0 : 2);
@endphp

@section('document-title', $docTitle)

@section('report-title', $report->reportTitle())

@section('report-subtitle')
    {{ $report->modulo }}
@endsection

@push('report-styles')
<style>
    body {
        font-size: 8.2px;
        line-height: 1.25;
    }
    .section-block {
        margin-bottom: 8px;
    }
    .section-head {
        margin-bottom: 4px;
        padding-bottom: 3px;
        font-size: 8px;
        border-bottom-width: 1px;
    }
    table.field-sheet {
        font-size: 8px;
    }
    table.field-sheet td {
        padding: 3px 5px;
    }
    table.field-sheet .lbl {
        width: 18%;
        font-size: 7px;
    }
    table.data {
        margin-top: 3px;
    }
    table.data th,
    table.data td {
        padding: 3px 4px;
    }
    table.data th {
        font-size: 7.2px;
    }
    .account-card {
        margin-bottom: 8px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
    }
    .account-card td {
        border: none;
        border-right: 1px solid #e2e8f0;
        padding: 4px 8px;
        vertical-align: middle;
        text-align: center;
        line-height: 1.25;
        height: 32px;
    }
    .account-card td:last-child {
        border-right: none;
    }
    .account-label {
        display: block;
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 1px;
        text-align: center;
    }
    .account-value {
        display: block;
        font-size: 9px;
        font-weight: bold;
        color: #0f172a;
        line-height: 1.2;
        text-align: center;
    }
    .attention-type {
        display: block;
        color: #0f172a;
        font-size: 9px;
        font-weight: bold;
        line-height: 1.2;
        text-transform: uppercase;
    }
    .attention-cell {
        text-align: center;
        white-space: nowrap;
    }
    .total-row td {
        font-weight: bold;
        background: #f8fafc;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .service-description {
        font-weight: bold;
        color: #0f172a;
    }
    .service-meta {
        display: none;
    }
    .summary-row td {
        background: #ffffff;
        font-weight: bold;
    }
    .signature-wrap {
        text-align: center;
        margin-top: 4px;
    }
    .signature-card {
        display: inline-block;
        min-width: 62mm;
        max-width: 112mm;
        border: 1px solid #cbd5e1;
        vertical-align: top;
    }
    .signature-title {
        background: #f1f5f9;
        color: #334155;
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.04em;
        text-align: center;
        text-transform: uppercase;
        padding: 4px 6px;
    }
    .signature-body {
        height: 82px;
        position: relative;
    }
    .signature-line {
        position: absolute;
        left: 9mm;
        right: 9mm;
        bottom: 8px;
        border-top: 1px solid #334155;
        padding-top: 4px;
        color: #475569;
        font-size: 7px;
        text-align: center;
        white-space: nowrap;
    }
</style>
@endpush

@section('report-body')
    <table class="account-card" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 25%;">
                <span class="account-label">N° de cuenta</span>
                <span class="account-value tabular">{{ $report->nroCuenta }}</span>
            </td>
            <td style="width: 25%;">
                <span class="account-label">Fecha atención</span>
                <span class="account-value tabular">{{ $report->fechaAtencion }}</span>
            </td>
            <td style="width: 25%;">
                <span class="account-label">Hora atención</span>
                <span class="account-value tabular">{{ $report->horaAtencion }}</span>
            </td>
            <td class="attention-cell" style="width: 25%;">
                @if($report->motivo !== '—')
                    <span class="attention-type">{{ $report->motivo }}</span>
                @else
                    <span class="account-value">—</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-block">
        <div class="section-head">Datos de la atención</div>
        <table class="field-sheet" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <tr>
                <td class="lbl">Fecha de cita</td>
                <td class="val tabular">{{ $report->fechaCita }}</td>
                <td class="lbl">Hora de cita</td>
                <td class="val tabular">{{ $report->horaCita }}</td>
            </tr>
            <tr>
                <td class="lbl">Especialidad</td>
                <td class="val">{{ $report->especialidad }}</td>
                <td class="lbl">Médico</td>
                <td class="val">{{ $report->medicoProgramado }}</td>
            </tr>
            <tr>
                <td class="lbl">Consultorio</td>
                <td class="val">{{ $report->consultorio }}</td>
                <td class="lbl">IAFAS</td>
                <td class="val">{{ $report->iafa }}</td>
            </tr>
            <tr>
                <td class="lbl">Contratante</td>
                <td class="val">{{ $report->contratante }}</td>
                <td class="lbl">Plan</td>
                <td class="val">{{ $report->plan }}</td>
            </tr>
        </table>
    </div>

    <div class="section-block">
        <div class="section-head">Datos del paciente</div>
        <table class="field-sheet" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <tr>
                <td class="lbl">Paciente</td>
                <td class="val" colspan="3" style="font-weight: bold;">{{ $report->paciente }}</td>
            </tr>
            <tr>
                <td class="lbl">N° Historia</td>
                <td class="val tabular">{{ $report->numeroHistoriaClinica }}</td>
                <td class="lbl">Edad</td>
                <td class="val">{{ $report->edad }}</td>
            </tr>
            <tr>
                <td class="lbl">Titular</td>
                <td class="val">{{ $report->titular }}</td>
                <td class="lbl">Parentesco</td>
                <td class="val">{{ $report->parentesco }}</td>
            </tr>
        </table>
    </div>

    <div class="section-block">
        <div class="section-head">Servicios registrados</div>
        <table class="data" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="width: 11%;">Código</th>
                    <th>Descripción del servicio</th>
                    @if($report->showDescuento)
                        <th style="width: 7%;">Dscto.</th>
                    @endif
                    @if($report->showAumento)
                        <th style="width: 7%;">Aum.</th>
                    @endif
                    <th style="width: 7%;">Cant.</th>
                    <th style="width: 12%;">Subtotal</th>
                    <th style="width: 10%;">IGV</th>
                    <th style="width: 12%;">Total</th>
                    @if(! $report->esPrecioDirecto)
                        <th style="width: 12%;">Pago asegur.</th>
                        <th style="width: 12%;">Pago paciente</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($report->servicios as $servicio)
                    <tr>
                        <td class="text-center tabular">{{ $servicio['codigo'] }}</td>
                        <td>
                            <div class="service-description">{{ $servicio['descripcion'] }}</div>
                        </td>
                        @if($report->showDescuento)
                            <td class="text-center tabular">{{ $servicio['descuento'] }}</td>
                        @endif
                        @if($report->showAumento)
                            <td class="text-center tabular">{{ $servicio['aumento'] }}</td>
                        @endif
                        <td class="text-center tabular">{{ $servicio['cantidad'] }}</td>
                        <td class="text-right tabular">{{ $servicio['precio_sin_igv'] }}</td>
                        <td class="text-right tabular">{{ $servicio['igv'] }}</td>
                        <td class="text-right tabular">{{ $servicio['precio_con_igv'] }}</td>
                        @if(! $report->esPrecioDirecto)
                            <td class="text-right tabular">{{ $servicio['pago_aseguradora'] }}</td>
                            <td class="text-right tabular">{{ $servicio['pago_paciente'] }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $servicesColspan }}" class="text-center">No hay servicios registrados.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="{{ 3 + ($report->showDescuento ? 1 : 0) + ($report->showAumento ? 1 : 0) }}" class="text-right">Totales</td>
                    <td class="text-right tabular">{{ $report->totalSinIgv }}</td>
                    <td class="text-right tabular">{{ $report->totalIgv }}</td>
                    <td class="text-right tabular">{{ $report->totalConIgv }}</td>
                    @if(! $report->esPrecioDirecto)
                        <td></td>
                        <td class="text-right tabular">{{ $report->montoAPagar }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <div class="section-head">Conformidad del paciente</div>
        <div class="signature-wrap">
            <div class="signature-card">
                <div class="signature-title">Firma del paciente</div>
                <div class="signature-body">
                    <div class="signature-line">{{ $report->firmaPaciente }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
