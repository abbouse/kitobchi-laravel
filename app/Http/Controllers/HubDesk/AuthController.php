<?php

namespace App\Http\Controllers\HubDesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('hub_web')->check()) {
            return redirect()->route('hubdesk.index');
        }

        return view('hubdesk.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('hub_web')->attempt([
            'username' => trim((string) $credentials['username']),
            'password' => (string) $credentials['password'],
            'is_active' => true,
        ])) {
            return back()->withErrors([
                'username' => 'Login yoki parol noto‘g‘ri.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->route('hubdesk.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('hub_web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('hubdesk.login');
    }
}
