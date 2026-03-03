<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stories;
use App\Models\Users;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoriesController extends Controller
{
    /**
     * barcha contestlar ro'yxati mainpage
     */
    public function index()
{
    $categories = Stories::where('type', 'cat')
        ->orderBy('updated_at', 'DESC')
        ->limit(10)
        ->with(['relatedStories' => function ($query) {
            $query->select('id', 'cat_id', 'url', 'status', 'updated_at');
        }])
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $categories,
    ], 201);
}
}
