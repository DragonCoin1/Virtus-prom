<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_login' => ['required', 'string'],
            'password'   => ['required', 'string'],
        ]);

        $eloquentUser = \App\Models\User::query()
            ->where('user_login', $request->input('user_login'))
            ->first();

        if (!$eloquentUser || (int)$eloquentUser->user_is_active !== 1) {
            return back()->withErrors(['user_login' => 'Аккаунт отключён']);
        }

        $password = $request->input('password');
        if (!Hash::check($password, (string) $eloquentUser->password)) {
            return back()->withErrors(['user_login' => 'Неверный логин или пароль']);
        }

        // If hashing params changed, rehash stored password (canonical field: password)
        if (Hash::needsRehash((string) $eloquentUser->password)) {
            $eloquentUser->password = $password; // hashed cast will rehash
        }

        Auth::login($eloquentUser);

        $eloquentUser->user_last_login_at = now();
        $eloquentUser->save();

        return redirect()->route('module.route_actions');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
