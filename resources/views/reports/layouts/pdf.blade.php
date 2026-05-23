<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('report-title', 'Reporte')</title>
    <style>
        @page {
            margin: 14mm 14mm 26mm 14mm;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.4;
        }
        table.report-sheet {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        table.report-sheet > tbody > tr > td {
            padding: 10px 12px;
            vertical-align: top;
            border: none;
        }
        table.report-sheet-footer {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }
        table.report-sheet-footer > tbody > tr > td {
            padding: 8px 12px;
            border: none;
        }
        .report-footer-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        .muted { color: #64748b; }
        .text-right { text-align: right; }
        .tabular { font-variant-numeric: tabular-nums; }
        table.report-banner {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 14px;
        }
        table.report-banner td {
            background: #0f4c75;
            color: #ffffff;
            text-align: center;
            padding: 10px 12px;
            border: none;
        }
        .report-banner__title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .report-banner__subtitle {
            font-size: 9px;
            margin-top: 4px;
            color: #bfdbfe;
            letter-spacing: 0.02em;
        }
        .section-head {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #0f4c75;
            margin: 0 0 8px;
            padding: 0 0 5px;
            border-bottom: 2px solid #0f4c75;
        }
        .section-block { margin-bottom: 14px; }
        table.field-sheet {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            border: 1px solid #cbd5e1;
        }
        table.field-sheet td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.field-sheet .lbl {
            width: 24%;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #475569;
            background: #f1f5f9;
        }
        table.field-sheet .val {
            color: #0f172a;
        }
        table.meta-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
        }
        table.meta-strip td {
            padding: 4px 8px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
            text-align: center;
            line-height: 1.25;
        }
        table.meta-strip td:last-child {
            border-right: none;
        }
        table.meta-strip .meta-lbl {
            display: block;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 1px;
            text-align: center;
            line-height: 1.2;
        }
        table.meta-strip .meta-val {
            display: block;
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            text-align: center;
            line-height: 1.2;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
        }
        table.data th,
        table.data td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            vertical-align: top;
        }
        table.data th {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 8.5px;
        }
        .page-break { page-break-after: always; }
    </style>
    @stack('report-styles')
</head>
<body>
    <table class="report-sheet" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
        <tr>
            <td>
                @include('reports.partials.institution-header', ['meta' => $meta])

                <table class="report-banner" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <div class="report-banner__title">@yield('report-title', 'Reporte')</div>
                            @hasSection('report-subtitle')
                                <div class="report-banner__subtitle">@yield('report-subtitle')</div>
                            @endif
                        </td>
                    </tr>
                </table>

                @yield('report-body')
            </td>
        </tr>
    </table>

    <div class="report-footer-fixed">
        <table class="report-sheet-footer" width="100%" cellspacing="0" cellpadding="0" border="1" style="border:1px solid #cbd5e1; border-collapse:collapse;">
            <tr>
                <td>
                    @include('reports.partials.report-footer', ['meta' => $meta])
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
