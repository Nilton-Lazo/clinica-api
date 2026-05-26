<?php

namespace App\Core\reporting;

use App\Core\reporting\Contracts\ReportViewData;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportExportResponse
{
    public function __construct(
        private PdfReportRenderer $pdf,
    ) {}

    public function pdf(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        ?string $filename = null,
    ): \Illuminate\Http\Response {
        $name = $filename ?? ReportFilename::build($report->reportKey(), ReportFormat::Pdf);

        return $this->pdf->download($bladeView, $report, $meta, $name);
    }

    public function pdfInline(
        string $bladeView,
        ReportViewData $report,
        ReportGenerationContext $meta,
        ?string $filename = null,
    ): \Illuminate\Http\Response {
        $name = $filename ?? ReportFilename::build($report->reportKey(), ReportFormat::Pdf);

        return $this->pdf->streamInline($bladeView, $report, $meta, $name);
    }

    public function excel(object $export, ReportViewData $report, ?string $filename = null): BinaryFileResponse
    {
        $name = $filename ?? ReportFilename::build($report->reportKey(), ReportFormat::Xlsx);

        return Excel::download($export, $name);
    }
}
