<?php

namespace App\Modules\telemetria\Services;

use App\Core\audit\Facades\Audit;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;

class NavigationService
{
    public function track(array $data, Authenticatable $user, Request $request): void
    {
        $meta = [
            'client' => [
                'path' => $data['path'],
                'screen' => $data['screen'] ?? null,
                'module' => $data['module'] ?? null,
            ],
        ];

        Audit::log(
            'ui.navigation',
            'Navegación UI',
            null,
            null,
            $meta,
            'success',
            200
        );
    }
}
