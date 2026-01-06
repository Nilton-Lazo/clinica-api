<?php

namespace App\Modules\telemetria\Controllers;

use App\Modules\telemetria\Requests\NavigationEventRequest;
use App\Modules\telemetria\Services\NavigationService;
use Illuminate\Http\JsonResponse;

class NavigationController
{
    public function __invoke(NavigationEventRequest $request, NavigationService $service): JsonResponse
    {
        $service->track($request->validated(), $request->user(), $request);

        return response()->json(['ok' => true]);
    }
}
