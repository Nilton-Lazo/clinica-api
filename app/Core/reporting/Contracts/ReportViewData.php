<?php

namespace App\Core\reporting\Contracts;

interface ReportViewData
{
    public function reportKey(): string;

    public function reportTitle(): string;

    public function toArray(): array;
}
