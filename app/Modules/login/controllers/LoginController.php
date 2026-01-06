<?php

namespace App\Modules\login\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\login\Requests\LoginRequest;
use App\Modules\login\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(LoginRequest $request, LoginService $service): JsonResponse
    {
        $result = $service->login(
            $request->input('identifier'),
            $request->input('password')
        );

        return response()->json($result);
    }

    public function logout(Request $request, LoginService $service): JsonResponse
    {
        $service->logout($request->user());

        return response()->json([
            'message' => 'Sesión cerrada',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'nombres' => $user->nombres,
            'apellido_paterno' => $user->apellido_paterno,
            'apellido_materno' => $user->apellido_materno,
            'nivel' => $user->nivel,
            'estado' => $user->estado,
        ]);
    }
}
