<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller {

    public function index(): JsonResponse {
        // Возвращаем список пользователей. Можно также подгрузить отношения, например 'role', 'unit'
        $users = User::orderBy('id', 'desc')->get();

        return response()->json([
            'data' => $users
        ]);
    }

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name'     => 'required|string',
            'login'    => 'required|string|max:255|unique:users,login',
            'password' => 'required|string|min:4',
            'role_id'  => 'required|integer',
            'unit_id'  => 'required|integer|exists:units,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id): JsonResponse {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'login')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:4',
            'role_id'  => 'required|integer',
            'unit_id'  => 'required|integer|exists:units,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy($id): JsonResponse {
        $user = User::findOrFail($id);

        if (auth()->id == $id) {
            return response()->json(['error' => 'Вы не можете удалить сами себя'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Пользователь успешно удален']);
    }
}
