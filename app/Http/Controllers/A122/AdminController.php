<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Admin::query();
        $tab = $request->input('tab', 'active');

        match ($tab) {
            'inactive' => $query->where('is_active', false),
            'all' => null,
            default => $query->where('is_active', true),
        };

        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
            );
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $admins = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Admin::count(),
            'active' => Admin::where('is_active', true)->count(),
            'inactive' => Admin::where('is_active', false)->count(),
        ];

        return view('a122.admins.index', compact('admins', 'counts', 'tab'));
    }

    public function create()
    {
        return view('a122.admins.edit');
    }

    public function show(Admin $admin)
    {
        return view('a122.admins.show', compact('admin'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:superadmin,admin,moderator',
            'is_active' => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        Admin::create($data);
        return redirect()->route('admin.admins.index')->with('success', "Admin qo'shildi.");
    }

    public function edit(Admin $admin)
    {
        return view('a122.admins.edit', compact('admin'));
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', Rule::unique('admins', 'email')->ignore($admin->id)],
            'role'     => 'required|in:superadmin,admin,moderator',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }
        $data['is_active'] = $request->boolean('is_active');

        $admin->update($data);
        return redirect()->route('admin.admins.index')->with('success', "Admin yangilandi.");
    }

    public function destroy(Admin $admin)
    {
        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', "Admin o'chirildi.");
    }

    public function toggle(Admin $admin)
    {
        $admin->update(['is_active' => ! $admin->is_active]);

        return back()->with('success', $admin->is_active ? 'Admin faollashtirildi.' : 'Admin bloklandi.');
    }
}
