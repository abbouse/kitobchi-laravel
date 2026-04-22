<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('panel')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('a122.auth.login');
    }

    public function login(Request $request)
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (! Auth::guard('panel')->attempt(
            ['email' => $creds['email'], 'password' => $creds['password'], 'is_active' => true],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Email yoki parol noto'g'ri, yoki akkaunt bloklangan.");
        }

        $admin = Auth::guard('panel')->user();
        $admin->update([
            'last_login_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('panel')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', "Tizimdan chiqildi.");
    }

    public function profile()
    {
        $admin = Auth::guard('panel')->user();

        return view('a122.auth.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('panel')->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('admins', 'email')->ignore($admin->id)],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            if (! Hash::check($request->input('current_password'), $admin->password)) {
                return back()->with('error', "Joriy parol noto'g'ri.");
            }

            $data['password'] = Hash::make($request->input('password'));
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('admin_avatars', 'public');
        }

        $admin->update($data);

        return back()->with('success', "Profil yangilandi.");
    }
}
