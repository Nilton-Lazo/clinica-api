<?php

namespace App\Modules\admision\controllers\pacientes;

use App\Core\reporting\ReportExportResponse;
use App\Core\reporting\ReportFormat;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\requests\pacientes\PacienteFiliacionExportRequest;
use App\Modules\admision\services\pacientes\HojaFiliacionPacienteReportService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PacienteReportController extends Controller
{
    public function __construct(
        private HojaFiliacionPacienteReportService $filiacion,
        private ReportExportResponse $exports,
    ) {}

    public function hojaFiliacion(PacienteFiliacionExportRequest $request, Paciente $paciente): Response|View
    {
        $this->authorize('view', $paciente);

        $format = ReportFormat::tryFromRequest($request->validated('format'));
        if ($format === null) {
            abort(422, 'Formato de reporte no válido.');
        }

        $report = $this->filiacion->buildViewData($paciente, $request->user());
        $meta = $this->filiacion->generationContext($request->user());
        $view = 'reports.admision.hoja-filiacion-paciente';

        $wantsPreview = $request->boolean('preview') && config('reports.preview_enabled');

        if ($format === ReportFormat::Pdf && $wantsPreview) {
            return app(\App\Core\reporting\PdfReportRenderer::class)->renderHtml($view, $report, $meta);
        }

        if ($format === ReportFormat::Pdf) {
            $filename = $this->filiacion->filenameForExport($report, $paciente, $format);

            if ($request->boolean('inline')) {
                return $this->exports->pdfInline($view, $report, $meta, $filename);
            }

            return $this->exports->pdf($view, $report, $meta, $filename);
        }

        abort(422, 'Formato de reporte no soportado.');
    }
}
