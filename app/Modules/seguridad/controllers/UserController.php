<?php

namespace App\Modules\seguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\seguridad\Requests\CreateUserRequest;
use App\Modules\seguridad\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
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
