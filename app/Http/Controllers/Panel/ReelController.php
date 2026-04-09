<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Models\ReelItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReelController extends Controller
{
    // ── Index ──────────────────────────────────────────────
    public function index()
    {
        $reels = Reel::withCount('items')
            ->orderBy('order')
            ->paginate(20);

        return view('panel.reels.index', compact('reels'));
    }

    // ── Create ─────────────────────────────────────────────
    public function create()
    {
        $nextOrder = (Reel::max('order') ?? 0) + 1;
        return view('panel.reels.create', compact('nextOrder'));
    }

    // ── Store ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'required|integer|min:0',
        ]);

        $reel = Reel::create([
            'title'       => $request->title,
            'description' => $request->description,
            'order'       => $request->order,
        ]);

        return redirect()->route('panel.reels.show', $reel)
            ->with('success', 'Reel yaratildi.');
    }

    // ── Show ───────────────────────────────────────────────
    public function show(Reel $reel)
    {
        $reel->load('items');
        return view('panel.reels.show', compact('reel'));
    }

    // ── Edit ───────────────────────────────────────────────
    public function edit(Reel $reel)
    {
        return view('panel.reels.edit', compact('reel'));
    }

    // ── Update ─────────────────────────────────────────────
    public function update(Request $request, Reel $reel)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'required|integer|min:0',
        ]);

        $reel->update([
            'title'       => $request->title,
            'description' => $request->description,
            'order'       => $request->order,
        ]);

        return redirect()->route('panel.reels.show', $reel)
            ->with('success', 'Reel yangilandi.');
    }

    // ── Destroy ────────────────────────────────────────────
    public function destroy(Reel $reel)
    {
        // Barcha videolarni o'chiramiz
        foreach ($reel->items as $item) {
            $this->deleteItemFiles($item);
        }

        $reel->delete();

        return redirect()->route('panel.reels.index')
            ->with('success', 'Reel va barcha videolari o\'chirildi.');
    }

    // ── ReelItem: yaratish ─────────────────────────────────
    public function storeItem(Request $request, Reel $reel)
    {
        $request->validate([
            'video_720p' => 'required|file|mimes:mp4,webm,mov|max:204800',
            'video_480p' => 'nullable|file|mimes:mp4,webm,mov|max:102400',
            'video_360p' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
            'order'      => 'required|integer|min:0',
        ]);

        // 3 ta video uchun bir xil nom (extension farqli bo'lishi mumkin)
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

        return redirect()->route('panel.reels.show', $reel)
            ->with('success', 'Video qo\'shildi.');
    }

    // ── ReelItem: o'chirish ────────────────────────────────
    public function destroyItem(Reel $reel, ReelItem $item)
    {
        // Reel ga tegishligini tekshirish
        abort_if($item->reel_id !== $reel->id, 403);

        $this->deleteItemFiles($item);
        $item->delete();

        return back()->with('success', 'Video o\'chirildi.');
    }

    // ── ReelItem: tartibni saqlash (drag & drop) ───────────
    public function reorderItems(Request $request, Reel $reel)
    {
        $request->validate([
            'items'   => 'required|array',
            'items.*' => 'integer|exists:reel_items,id',
        ]);

        DB::transaction(function () use ($request, $reel) {
            foreach ($request->items as $index => $id) {
                ReelItem::where('id', $id)
                    ->where('reel_id', $reel->id)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    // ── Yordamchi: fayl o'chirish ──────────────────────────
    private function deleteItemFiles(ReelItem $item): void
    {
        foreach (['video_720p', 'video_480p', 'video_360p'] as $field) {
            if ($item->$field) {
                Storage::disk('public')->delete($item->$field);
            }
        }
    }
}