<?php

use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'token.fresh',
    'throttle:api',
])->group(function () {
    Route::get('system/datetime', [SystemController::class, 'datetime']);
    Route::get('system/codigos', [SystemController::class, 'codigos']);
});
