<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->refresh();

            DB::table('categories')->insert([
                [
                    'user_id' => $user->id,
                    'name' => 'Жалақы',
                    'type' => 'income',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $user->id,
                    'name' => 'Азық-түлік',
                    'type' => 'expense',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'role' => $user->roleName(),
            'token' => $user->createToken('finpilot')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'role' => $user->roleName(),
            'token' => $user->createToken('finpilot')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json(
            User::select('id', 'name', 'email', 'role_id')->get()
        );
    }
}
