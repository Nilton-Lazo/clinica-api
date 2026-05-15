<?php

namespace App\Http\Controllers;

use App\Core\support\CodigoCorrelativo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function datetime(): JsonResponse
    {
        $tz = (string) config('app.timezone');
        $now = Carbon::now($tz);

        return response()->json([
            'data' => [
                'timezone' => $tz,
                'iso' => $now->toIso8601String(),
            ],
        ]);
    }

    public function codigos(): JsonResponse
    {
        return response()->json([
            'data' => CodigoCorrelativo::publicConfig(),
        ]);
    }
}
