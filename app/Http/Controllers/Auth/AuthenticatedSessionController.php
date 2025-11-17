<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse; // <-- PASTIKAN INI DITAMBAHKAN

class AuthenticatedSessionController extends Controller
{
    // ... (method create() biarkan saja) ...

    /**
     * Handle an incoming authentication request.
     *
     * (GANTI SELURUH METHOD DI BAWAH INI)
     */
    public function store(LoginRequest $request): RedirectResponse | JsonResponse
    {
        // 1. Lakukan otentikasi (cek email & password)
        $request->authenticate();

        // 2. Cek apakah ini request API (dari Postman/frontend)
        if ($request->wantsJson()) {

            // 3. Ambil data user
            $user = $request->user();

            // 4. Hapus token lama (jika ada) dan buat token baru
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // 5. Kembalikan token sebagai JSON
            return response()->json(['token' => $token]);
        }

        // 6. Jika ini BUKAN request API (misal dari browser web),
        //    baru kita mainkan session-nya.
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
