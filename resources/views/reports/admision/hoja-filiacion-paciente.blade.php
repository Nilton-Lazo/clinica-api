@extends('reports.layouts.pdf')

@php
    $hc = $report->numeroHistoriaClinica !== '—' ? $report->numeroHistoriaClinica : null;
    $docTitle = $hc
        ? 'HC '.$hc.' — Filiación'
        : 'Hoja de filiación';
@endphp

@section('document-title', $docTitle)

@section('report-title', $report->reportTitle())

@section('report-subtitle')
    {{ $report->modulo }} · Registro de admisión
@endsection

@push('report-styles')
<style>
    .auth-intro {
        font-size: 7.5px;
        color: #64748b;
        margin-bottom: 8px;
        line-height: 1.45;
    }
    table.auth-columns {
        width: 100%;
        border-collapse: collapse;
    }
    table.auth-columns > tbody > tr > td {
        width: 50%;
        vertical-align: top;
        padding: 0 4px;
        border: none;
    }
    table.auth-person {
        width: 100%;
        border-collapse: collapse;
    }
    table.auth-person td {
        border: 1px solid #cbd5e1;
        vertical-align: top;
        padding: 0;
    }
    .auth-role {
        background: #e2e8f0;
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #334155;
        text-align: center;
        padding: 6px 8px;
    }
    .auth-cell-label {
        font-size: 7.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        padding: 6px 8px 4px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        text-align: center;
    }
    .auth-body-cell {
        padding: 0;
        vertical-align: top;
        text-align: center;
    }
    table.auth-body-fill {
        width: 100%;
        border-collapse: collapse;
    }
    table.auth-body-fill td {
        border: none;
        padding: 0;
    }
    td.auth-sign-spacer {
        height: 86px;
        font-size: 1px;
        line-height: 1px;
    }
    td.auth-sign-bottom {
        vertical-align: bottom;
        text-align: center;
        padding: 0 12px 2px;
        height: 40px;
    }
    td.auth-print-center {
        vertical-align: middle;
        text-align: center;
        padding: 0;
        height: 113px;
    }
    table.auth-print-pad {
        margin: 0 auto;
        border-collapse: collapse;
    }
    table.auth-print-pad td {
        padding: 12px 14px 10px;
        text-align: center;
        vertical-align: middle;
        border: none;
    }
    .auth-sign-rule {
        width: 78%;
        max-width: 140px;
        margin: 0 auto;
        border-top: 1px solid #334155;
        padding-top: 4px;
        font-size: 8px;
        color: #475569;
        text-align: center;
    }
    .auth-fingerprint-inner {
        width: 77px;
        height: 93px;
        margin: 0 auto 6px;
        border: 1px dashed #94a3b8;
        background: #f8fafc;
    }
    .auth-hint {
        font-size: 7px;
        color: #94a3b8;
        text-align: center;
        margin: 0;
    }
    .legal-note {
        margin-top: 10px;
        padding: 8px 10px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        font-size: 7.5px;
        color: #64748b;
        line-height: 1.45;
    }
</style>
@endpush

@section('report-body')
    <div class="section-block">
        <div class="section-head">Información de admisión</div>
        <table class="meta-strip" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <tr>
                <td style="width: 25%;">
                    <span class="meta-lbl">Fecha de admisión</span>
                    <span class="meta-val tabular">{{ $report->fechaAdmision }}</span>
                </td>
                <td style="width: 15%;">
                    <span class="meta-lbl">Hora</span>
                    <span class="meta-val tabular">{{ $report->horaAdmision }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="meta-lbl">Usuario</span>
                    <span class="meta-val">{{ $report->usuario }}</span>
                </td>
                <td style="width: 35%;">
                    <span class="meta-lbl">Módulo</span>
                    <span class="meta-val">{{ $report->modulo }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-block">
        <div class="section-head">Datos del paciente</div>
        <table class="field-sheet" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <tr>
                <td class="lbl">N° Historia clínica</td>
                <td class="val tabular" style="width: 26%;">{{ $report->numeroHistoriaClinica }}</td>
                <td class="lbl">N° Referencia</td>
                <td class="val tabular" style="width: 26%;">{{ $report->numeroReferencia }}</td>
            </tr>
            <tr>
                <td class="lbl">Apellido paterno</td>
                <td class="val">{{ $report->apellidoPaterno }}</td>
                <td class="lbl">Apellido materno</td>
                <td class="val">{{ $report->apellidoMaterno }}</td>
            </tr>
            <tr>
                <td class="lbl">Nombres</td>
                <td class="val" colspan="3" style="font-weight: bold;">{{ $report->nombresCompletos }}</td>
            </tr>
            <tr>
                <td class="lbl">Domicilio actual</td>
                <td class="val" colspan="3">{{ $report->domicilioActual }}</td>
            </tr>
            <tr>
                <td class="lbl">Teléfono</td>
                <td class="val">{{ $report->telefono }}</td>
                <td class="lbl">Ocupación</td>
                <td class="val">{{ $report->ocupacion }}</td>
            </tr>
            <tr>
                <td class="lbl">Fecha de nacimiento</td>
                <td class="val tabular">{{ $report->fechaNacimiento }}</td>
                <td class="lbl">Edad</td>
                <td class="val">{{ $report->edad }}</td>
            </tr>
            <tr>
                <td class="lbl">Estado civil</td>
                <td class="val">{{ $report->estadoCivil }}</td>
                <td class="lbl">Sexo</td>
                <td class="val">{{ $report->sexo }}</td>
            </tr>
            <tr>
                <td class="lbl">Lugar de nacimiento</td>
                <td class="val">{{ $report->lugarNacimiento }}</td>
                <td class="lbl">Nacionalidad</td>
                <td class="val">{{ $report->nacionalidad }}</td>
            </tr>
            <tr>
                <td class="lbl">Parentesco (seguro)</td>
                <td class="val">{{ $report->parentescoConPaciente }}</td>
                <td class="lbl">Médico tratante</td>
                <td class="val">{{ $report->medicoTratante }}</td>
            </tr>
            <tr>
                <td class="lbl">Familiar de emergencia</td>
                <td class="val">{{ $report->familiarResponsable }}</td>
                <td class="lbl">Teléfono familiar</td>
                <td class="val">{{ $report->telefonoFamiliar }}</td>
            </tr>
        </table>
    </div>

    <div class="section-block">
        <div class="section-head">Autorización — Firma y huella dactilar</div>
        <div class="auth-intro">
            Declaro que los datos consignados son verídicos. Registre su firma manuscrita en el espacio indicado y su huella dactilar en el recuadro correspondiente.
        </div>
        <table class="auth-columns" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <table class="auth-person" width="100%" cellspacing="0" cellpadding="0" border="1" style="width:100%; border-collapse:collapse; border:1px solid #94a3b8;">
                        <tr>
                            <td colspan="2" class="auth-role">Paciente</td>
                        </tr>
                        <tr>
                            <td class="auth-body-cell" style="width: 55%; border-right: 1px solid #cbd5e1;">
                                <div class="auth-cell-label">Firma manuscrita</div>
                                <table class="auth-body-fill" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="auth-sign-spacer">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td class="auth-sign-bottom">
                                            <div class="auth-sign-rule">Firma del paciente</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="auth-body-cell" style="width: 45%;">
                                <div class="auth-cell-label">Huella dactilar</div>
                                <table class="auth-body-fill" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="auth-print-center">
                                            <table class="auth-print-pad" cellspacing="0" cellpadding="0" align="center">
                                                <tr>
                                                    <td>
                                                        <div class="auth-fingerprint-inner"></div>
                                                        <div class="auth-hint">Pulgar derecho</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="auth-person" width="100%" cellspacing="0" cellpadding="0" border="1" style="width:100%; border-collapse:collapse; border:1px solid #94a3b8;">
                        <tr>
                            <td colspan="2" class="auth-role">Familiar responsable</td>
                        </tr>
                        <tr>
                            <td class="auth-body-cell" style="width: 55%; border-right: 1px solid #cbd5e1;">
                                <div class="auth-cell-label">Firma manuscrita</div>
                                <table class="auth-body-fill" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="auth-sign-spacer">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td class="auth-sign-bottom">
                                            <div class="auth-sign-rule">Firma del familiar responsable</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="auth-body-cell" style="width: 45%;">
                                <div class="auth-cell-label">Huella dactilar</div>
                                <table class="auth-body-fill" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="auth-print-center">
                                            <table class="auth-print-pad" cellspacing="0" cellpadding="0" align="center">
                                                <tr>
                                                    <td>
                                                        <div class="auth-fingerprint-inner"></div>
                                                        <div class="auth-hint">Pulgar derecho</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <div class="legal-note">
            Este documento forma parte de la historia clínica del paciente. Las firmas y huellas dactilares tienen carácter de declaración jurada sobre la veracidad de la información registrada.
        </div>
    </div>
@endsection
