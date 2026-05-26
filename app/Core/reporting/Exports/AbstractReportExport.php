<?php

namespace App\Core\reporting\Exports;

use App\Core\reporting\Contracts\ReportViewData;
use App\Core\reporting\ReportGenerationContext;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

abstract class AbstractReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected ReportViewData $report,
        protected ReportGenerationContext $meta,
    ) {}

    abstract public function sheets(): array;
}
