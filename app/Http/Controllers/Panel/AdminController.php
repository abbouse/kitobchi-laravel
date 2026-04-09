<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    // Faqat superadmin kirishi mumkin
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! Auth::guard('panel')->user()?->isSuperAdmin()) {
                abort(403, "Bu bo'lim faqat Super Admin uchun.");
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Admin::query();

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q->where('name','like',"%$s%")
                ->orWhere('email','like',"%$s%"));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $admins = $query->latest()->paginate(20)->withQueryString();

        return view('panel.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('panel.admins.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:admins,email',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => 'required|in:superadmin,admin,moderator',
            'permissions' => 'nullable|array',
            'is_active'   => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);

        // Superadmin uchun permissions kerak emas
        if ($data['role'] === 'superadmin') {
            $data['permissions'] = null;
        }

        $admin = Admin::create($data);

        return redirect()->route('panel.admins.show', $admin)
            ->with('success', "Admin yaratildi: {$admin->name}");
    }

    public function show(Admin $admin)
    {
        return view('panel.admins.show', compact('admin'));
    }

    public function edit(Admin $admin)
    {
        return view('panel.admins.edit', compact('admin'));
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => ['required','email', Rule::unique('admins','email')->ignore($admin->id)],
            'role'        => 'required|in:superadmin,admin,moderator',
            'permissions' => 'nullable|array',
            'is_active'   => 'boolean',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'required|min:8|confirmed']);
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($data['role'] === 'superadmin') {
            $data['permissions'] = null;
        }

        $admin->update($data);

        return redirect()->route('panel.admins.show', $admin)
            ->with('success', "Admin yangilandi.");
    }

    public function destroy(Admin $admin)
    {
        // O'zini o'chira olmaydi
        if ($admin->id === Auth::guard('panel')->id()) {
            return back()->with('error', "O'z akkauntingizni o'chira olmaysiz.");
        }

        $admin->delete();
        return redirect()->route('panel.admins.index')
            ->with('success', "Admin o'chirildi.");
    }

    // Bloklash / Bloqdan chiqarish
    public function toggle(Admin $admin)
    {
        if ($admin->id === Auth::guard('panel')->id()) {
            return back()->with('error', "O'z akkauntingizni bloqlay olmaysiz.");
        }

        $admin->update(['is_active' => ! $admin->is_active]);

        return back()->with('success',
            $admin->is_active ? "Admin faollashtirildi." : "Admin bloqlandi."
        );
    }
}