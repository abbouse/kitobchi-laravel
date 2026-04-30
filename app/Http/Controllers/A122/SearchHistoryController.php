<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = SearchHistory::query()
            ->with('user:id,name,lastname,username,phone')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('text', 'like', "%{$search}%")
                    ->orWhere('result_name', 'like', "%{$search}%")
                    ->orWhere('result_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('result_type', $request->input('type'));
        }

        if ($request->filled('scope')) {
            if ($request->input('scope') === 'guest') {
                $query->whereNull('user_id');
            } elseif ($request->input('scope') === 'user') {
                $query->whereNotNull('user_id');
            }
        }

        if ($request->filled('draft')) {
            $query->where('is_draft', $request->boolean('draft'));
        }

        $histories = $query->paginate(50)->withQueryString();

        return view('a122.search-history.index', [
            'histories' => $histories,
        ]);
    }
}
