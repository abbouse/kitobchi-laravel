<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookClub;
use App\Models\BookClubTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BookClubThemeController extends Controller
{
    /**
     * Mavzular ro'yxati — qidiruv + necha marta ishlatilgani bilan
     *
     * GET /api/book_club/themes
     * GET /api/book_club/themes?q=roman
     */
    public function index(Request $request)
    {
        try {
            $query = $request->input('q', '');

            $themes = BookClubTheme::where('status', 1)
                ->when($query, fn($q) => $q->where('name', 'like', "%{$query}%"))
                ->withCount('posts')          // necha marta ishlatilgani
                ->orderByDesc('posts_count')  // ko'p ishlatilgani yuqorida
                ->orderBy('name')
                ->limit(30)
                ->get()
                ->map(fn($t) => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'usage_count' => $t->posts_count,
                ]);

            return response()->json([
                'status' => 'success',
                'data'   => $themes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Yangi mavzu yaratish — agar mavjud bo'lsa uni qaytaradi
     *
     * POST /api/book_club/themes
     * body: { name: "Roman" }
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $request->validate([
                'name' => 'required|string|max:50',
            ]);

            $name = trim($request->input('name'));
            $slug = Str::slug($name);

            // Agar mavjud bo'lsa — shu themeni qaytaramiz
            $existing = BookClubTheme::where('slug', $slug)
                ->orWhere('name', $name)
                ->withCount('posts')
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'id'          => $existing->id,
                        'name'        => $existing->name,
                        'slug'        => $existing->slug,
                        'usage_count' => $existing->posts_count,
                    ],
                ]);
            }

            // Yangi mavzu yaratish
            $theme = BookClubTheme::create([
                'user_id' => $user->id,
                'name'    => $name,
                'slug'    => $slug,
                'status'  => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'          => $theme->id,
                    'name'        => $theme->name,
                    'slug'        => $theme->slug,
                    'usage_count' => 0,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}