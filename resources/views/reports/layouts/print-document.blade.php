<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('report-title', 'Reporte')</title>
    @include('reports.partials.print-document-styles')
    @stack('report-styles')
</head>
<body>
    <table class="doc-page" width="210mm" cellspacing="0" cellpadding="0" align="center">
        <tr>
            <td class="doc-body">
                <table class="doc-frame" width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td>
                            @include('reports.partials.institution-header', ['meta' => $meta])

                            <table class="report-banner" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <span class="report-banner__title">@yield('report-title', 'Reporte')</span>
                                        @hasSection('report-subtitle')
                                            <br>
                                            <span class="report-banner__subtitle">@yield('report-subtitle')</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @yield('report-body')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="doc-footer">
                <table class="footer-sheet" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            @include('reports.partials.report-footer', ['meta' => $meta])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
