<?php

use App\Core\notifications\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'token.fresh'])->prefix('notifications')->group(function () {
    Route::get('/',           [NotificationController::class, 'index']);
    Route::post('/read-all',  [NotificationController::class, 'markAllAsRead']);
    Route::patch('/{id}/read',[NotificationController::class, 'markAsRead'])->where('id', '[0-9]+');
});
