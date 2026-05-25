<?php

namespace App\Core\reporting;

use App\Core\reporting\Contracts\ReportViewData;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as IlluminateView;
use Spatie\Browsershot\Browsershot;
use Throwable;

final class BrowsershotPdfRenderer
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
        $html = $this->renderHtml($bladeView, $report, $meta)->render();
        $binary = $this->renderPdfBinary($html);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function renderPdfBinary(string $html): string
    {
        try {
            $shot = Browsershot::html($html)
                ->showBackground()
                ->format('A4')
                ->margins(0, 0, 0, 0)
                ->preferCssPageSize()
                ->timeout((int) config('reports.request_timeout_seconds', 120));

            if (config('reports.browsershot.no_sandbox')) {
                $shot->noSandbox();
            }

            $nodeBinary = config('reports.browsershot.node_binary');
            if (is_string($nodeBinary) && $nodeBinary !== '') {
                $shot->setNodeBinary($nodeBinary);
            }

            $npmBinary = config('reports.browsershot.npm_binary');
            if (is_string($npmBinary) && $npmBinary !== '') {
                $shot->setNpmBinary($npmBinary);
            }

            $chromePath = config('reports.browsershot.chrome_path');
            if (is_string($chromePath) && $chromePath !== '') {
                $shot->setChromePath($chromePath);
            }

            return $shot->pdf();
        } catch (Throwable $e) {
            throw new \RuntimeException(
                'No se pudo generar el PDF con el motor de impresión (Browsershot). Verifique Node.js, Puppeteer y Chrome en el servidor. Detalle: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
