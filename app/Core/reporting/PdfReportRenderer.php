<?php

namespace App\Core\reporting;

use App\Core\reporting\Contracts\ReportViewData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as IlluminateView;

final class PdfReportRenderer
{
    public function __construct(
        private BrowsershotPdfRenderer $browsershot,
    ) {}

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
        if ($this->usesBrowsershot()) {
            try {
                return $this->browsershot->download($bladeView, $report, $meta, $filename);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $this->downloadWithDompdf($bladeView, $report, $meta, $filename);
    }

    public function streamInline(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        string $filename,
    ): Response {
        if ($this->usesBrowsershot()) {
            $response = $this->browsershot->download($bladeView, $report, $meta, $filename);

            return response($response->getContent(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $pdf = $this->makeDompdf($bladeView, $report, $meta);

        return $pdf->stream($filename);
    }

    private function usesBrowsershot(): bool
    {
        return strtolower((string) config('reports.pdf_driver', 'browsershot')) === 'browsershot';
    }

    private function downloadWithDompdf(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        string $filename,
    ): Response {
        $pdf = $this->makeDompdf($bladeView, $report, $meta);

        return $pdf->download($filename);
    }

    private function makeDompdf(string $bladeView, ReportViewData $report, ReportGenerationContext $meta): \Barryvdh\DomPDF\PDF
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
        $pdf->setOption('defaultMediaType', 'print');
        $pdf->setOption('defaultPaperSize', (string) config('reports.pdf.paper', 'a4'));
        $pdf->setOption('defaultPaperOrientation', (string) config('reports.pdf.orientation', 'portrait'));

        return $pdf;
    }
}
