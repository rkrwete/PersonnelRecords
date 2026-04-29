<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller {
    public function login(Request $request):JsonResponse {
        $validate = $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('login', $validate['login'])->first();

        if (! $user || ! Hash::check($validate['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Неверные учетные данные.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role', 'managedUnit')
        ]);
    }

    public function logout(Request $request):JsonResponse {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Выход выполнен']);
    }

    public function me(Request $request):JsonResponse {
        $user = $request->user()->load('managedUnit');
        return response()->json([
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'unit_id'   => $user->unit_id,
            'unit_name' => $user->managedUnit?->name ?? 'Н/Д',
            'role_id' => $user->role_id,
        ]);
    }
}
