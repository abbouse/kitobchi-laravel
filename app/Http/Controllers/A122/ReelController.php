<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Models\ReelItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReelController extends Controller
{
    public function index(Request $request)
    {
        $reels = Reel::withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($inner) use ($search) {
                    $inner->where('id', $search)
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('order', $search);
                });
            })
            ->orderBy('order')
            ->paginate(20)
            ->withQueryString();

        return view('a122.reels.index', compact('reels'));
    }

    public function create()
    {
        $nextOrder = (Reel::max('order') ?? 0) + 1;
        return view('a122.reels.create', compact('nextOrder'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'required|integer|min:0',
        ]);

        $reel = Reel::create($request->only('title', 'description', 'order'));
        return redirect()->route('admin.reels.show', $reel)->with('success', 'Reel yaratildi.');
    }

    public function show(Reel $reel)
    {
        $reel->load('items');
        return view('a122.reels.show', compact('reel'));
    }

    public function edit(Reel $reel)
    {
        return view('a122.reels.edit', compact('reel'));
    }

    public function update(Request $request, Reel $reel)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'required|integer|min:0',
        ]);

        $reel->update($request->only('title', 'description', 'order'));
        return redirect()->route('admin.reels.show', $reel)->with('success', 'Reel yangilandi.');
    }

    public function destroy(Reel $reel)
    {
        foreach ($reel->items as $item) {
            foreach (['video_720p', 'video_480p', 'video_360p'] as $f) {
                if ($item->$f) Storage::disk('public')->delete($item->$f);
            }
        }
        $reel->delete();
        return redirect()->route('admin.reels.index')->with('success', "Reel o'chirildi.");
    }

    public function storeItem(Request $request, Reel $reel)
    {
        $request->validate([
            'video_720p' => 'required|file|mimes:mp4,webm,mov|max:204800',
            'video_480p' => 'nullable|file|mimes:mp4,webm,mov|max:102400',
            'video_360p' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
            'order'      => 'required|integer|min:0',
        ]);

        $baseName = 'v_' . time() . '_' . str()->random(8);
        $paths = [];

        foreach (['video_720p' => 'reels/720', 'video_480p' => 'reels/480', 'video_360p' => 'reels/360'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext  = strtolower($file->getClientOriginalExtension());
                $paths[$field] = $file->storeAs($dir, $baseName . '.' . $ext, 'public');
            } else {
                $paths[$field] = null;
            }
        }

        ReelItem::create([
            'reel_id'    => $reel->id,
            'video_720p' => $paths['video_720p'],
            'video_480p' => $paths['video_480p'],
            'video_360p' => $paths['video_360p'],
            'order'      => $request->order,
        ]);

        return redirect()->route('admin.reels.show', $reel)->with('success', "Video qo'shildi.");
    }

    public function destroyItem(Reel $reel, ReelItem $item)
    {
        abort_if($item->reel_id !== $reel->id, 403);
        foreach (['video_720p', 'video_480p', 'video_360p'] as $f) {
            if ($item->$f) Storage::disk('public')->delete($item->$f);
        }
        $item->delete();
        return back()->with('success', "Video o'chirildi.");
    }

    public function reorderItems(Request $request, Reel $reel)
    {
        $request->validate(['items' => 'required|array', 'items.*' => 'integer']);
        DB::transaction(function () use ($request, $reel) {
            foreach ($request->items as $index => $id) {
                ReelItem::where('id', $id)->where('reel_id', $reel->id)->update(['order' => $index + 1]);
            }
        });
        return response()->json(['ok' => true]);
    }
}
