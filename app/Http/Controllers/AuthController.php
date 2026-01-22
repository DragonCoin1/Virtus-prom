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

        $user = DB::table('users')
            ->where('user_login', $request->input('user_login'))
            ->first();

        if (!$user) {
            return back()->withErrors(['user_login' => 'Неверный логин или пароль']);
        }

        $password = $request->input('password');
        $passwordHash = $user->user_password_hash ?? null;
        if (!$passwordHash && isset($user->password)) {
            $passwordHash = $user->password;
        }

        $isValid = false;
        $needsRehash = false;
        if ($passwordHash) {
            if (Hash::check($password, $passwordHash)) {
                $isValid = true;
                $needsRehash = Hash::needsRehash($passwordHash);
            } elseif (hash_equals($passwordHash, $password)) {
                $isValid = true;
                $needsRehash = true;
            }
        }

        if (!$isValid) {
            return back()->withErrors(['user_login' => 'Неверный логин или пароль']);
        }

        $userId = $user->id ?? $user->user_id ?? null;
        if (!$userId) {
            return back()->withErrors(['user_login' => 'Неверный логин или пароль']);
        }

        $eloquentUser = \App\Models\User::find($userId);

        if (!$eloquentUser || (int)$eloquentUser->user_is_active !== 1) {
            return back()->withErrors(['user_login' => 'Аккаунт отключён']);
        }

        if ($needsRehash) {
            $newHash = Hash::make($password);
            $eloquentUser->user_password_hash = $newHash;
            if (property_exists($eloquentUser, 'password') || array_key_exists('password', $eloquentUser->getAttributes())) {
                $eloquentUser->password = $newHash;
            }
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
