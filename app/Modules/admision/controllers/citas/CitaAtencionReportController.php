<?php

namespace App\Modules\admision\controllers\citas;

use App\Core\reporting\PdfReportRenderer;
use App\Core\reporting\ReportExportResponse;
use App\Core\reporting\ReportFormat;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\requests\citas\CitaAtencionExportRequest;
use App\Modules\admision\services\citas\AtencionCitaReportService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CitaAtencionReportController extends Controller
{
    public function __construct(
        private AtencionCitaReportService $reportService,
        private ReportExportResponse $exports,
    ) {}

    public function reporteAtencion(CitaAtencionExportRequest $request, AgendaCita $cita): Response|View
    {
        $this->authorize('viewAny', AgendaCita::class);

        $format = ReportFormat::tryFromRequest($request->validated('format'));
        if ($format === null) {
            abort(422, 'Formato de reporte no válido.');
        }

        $report = $this->reportService->buildViewData($cita, $request->user());
        $meta = $this->reportService->generationContext($request->user());
        $view = 'reports.admision.atencion-cita';
        $wantsPreview = $request->boolean('preview') && config('reports.preview_enabled');

        if ($format === ReportFormat::Pdf && $wantsPreview) {
            return app(PdfReportRenderer::class)->renderHtml($view, $report, $meta);
        }

        if ($format === ReportFormat::Pdf) {
            $filename = $this->reportService->filenameForExport($report, $format);

            if ($request->boolean('inline')) {
                return $this->exports->pdfInline($view, $report, $meta, $filename);
            }

            return $this->exports->pdf($view, $report, $meta, $filename);
        }

        abort(422, 'Formato de reporte no soportado.');
    }
}
