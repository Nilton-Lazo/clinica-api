<?php

namespace App\Modules\seguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\seguridad\Requests\CreateUserRequest;
use App\Modules\seguridad\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $rows = User::query()
            ->where('estado', 'activo')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get(['id', 'username', 'name', 'nombres', 'apellido_paterno', 'apellido_materno']);

        $data = $rows->map(function (User $u) {
            $label = $this->formatUserLabel($u);

            return [
                'id' => $u->id,
                'username' => $u->username,
                'label' => $label,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    private function formatUserLabel(User $u): string
    {
        if (is_string($u->name) && trim($u->name) !== '') {
            return trim($u->name);
        }
        $parts = array_filter([
            $u->nombres ?? '',
            $u->apellido_paterno ?? '',
            $u->apellido_materno ?? '',
        ], fn ($x) => is_string($x) && trim($x) !== '');
        $full = trim(implode(' ', $parts));

        return $full !== '' ? $full : (string) $u->username;
    }

    public function store(
        CreateUserRequest $request,
        UserService $service
    ): JsonResponse {
        $user = $service->create($request->validated());

        return response()->json([
            'user' => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'nivel'    => $user->nivel,
                'estado'   => $user->estado,
            ],
        ], 201);
    }
}
