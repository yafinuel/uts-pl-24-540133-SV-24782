<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Upaya autentikasi gagal', [
                'ip' => $request->ip(),
                'email' => $request->email,
                'action' => 'user_login',
                'reason' => 'Kombinasi email atau password tidak valid',
            ]);

            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth')->plainTextToken;

        Log::info('Pengguna berhasil login', [
            'ip' => $request->ip(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => 'user_login',
            'reason' => 'Autentikasi kredensial berhasil',
        ]);

        return response()->json([
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }
}
