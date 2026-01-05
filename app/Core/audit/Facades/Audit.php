<?php

namespace App\Core\audit\Facades;

use Illuminate\Support\Facades\Facade;

class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Core\audit\AuditService::class;
    }
}
