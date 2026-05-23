<?php

namespace App\Core\reporting;

use App\Core\reporting\Contracts\ReportViewData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as IlluminateView;

final class PdfReportRenderer
{
    public function renderHtml(string $bladeView, ReportViewData $report, ReportGenerationContext $meta): IlluminateView
    {
        return View::make($bladeView, [
            'report' => $report,
            'meta' => $meta,
        ]);
    }

    public function download(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        string $filename,
    ): Response {
        $pdf = $this->makePdf($bladeView, $report, $meta);

        return $pdf->download($filename);
    }

    public function streamInline(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        string $filename,
    ): Response {
        $pdf = $this->makePdf($bladeView, $report, $meta);

        return $pdf->stream($filename);
    }

    private function makePdf(string $bladeView, ReportViewData $report, ReportGenerationContext $meta): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView($bladeView, [
            'report' => $report,
            'meta' => $meta,
        ])
            ->setPaper(
                (string) config('reports.pdf.paper', 'a4'),
                (string) config('reports.pdf.orientation', 'portrait'),
            );

        $pdf->setOption('isRemoteEnabled', (bool) config('reports.pdf.remote_enabled', false));
        $pdf->setOption('isPhpEnabled', false);
        $pdf->setOption('dpi', (int) config('reports.pdf.dpi', 96));

        return $pdf;
    }
}
