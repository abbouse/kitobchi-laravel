@extends('a122.layouts.admin')
@section('title', $reel->title)
@section('page-title', 'Reel: ' . $reel->title)

@section('content')
<section class="a122-section mb-4">
    <div class="a122-section-body">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
                <div class="metric-label">Reel nomi</div>
                <div class="metric-value text-xl">{{ \Illuminate\Support\Str::limit($reel->title, 14) }}</div>
                <div class="metric-meta">Kontent seti</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Elementlar</div>
                <div class="metric-value text-xl">{{ number_format($reel->items->count()) }}</div>
                <div class="metric-meta">Video birikmalari</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Tartib</div>
                <div class="metric-value text-xl">{{ $reel->order }}</div>
                <div class="metric-meta">Feed joylashuvi</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Yaratilgan</div>
                <div class="metric-value text-xl">{{ optional($reel->created_at)->format('d.m') ?: '—' }}</div>
                <div class="metric-meta">{{ optional($reel->created_at)->format('H:i') ?: 'Vaqt yo‘q' }}</div>
            </div>
        </div>
    </div>
</section>

<div class="mb-4 flex items-center justify-between">
    <a href="{{ route('admin.reels.index') }}" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Orqaga
    </a>
    <a href="{{ route('admin.reels.edit', $reel) }}" class="btn btn-primary flex items-center gap-2">
        <i data-lucide="pencil" class="w-4 h-4"></i> Tahrirlash
    </a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400 px-4 py-3 text-sm font-medium">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm font-medium">
        {{ session('error') }}
    </div>
@endif

{{-- Reel Info --}}
<div class="card p-5 mb-6">
    <h2 class="text-lg font-bold mb-3">Reel ma'lumotlari</h2>
    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <dt class="text-xs text-gray-500">Sarlavha</dt>
            <dd class="font-semibold mt-1">{{ $reel->title }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Tartib</dt>
            <dd class="font-semibold mt-1">{{ $reel->order }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Videolar soni</dt>
            <dd class="font-semibold mt-1">
                <span class="badge badge-info">{{ $reel->items->count() }}</span>
            </dd>
        </div>
        @if($reel->description)
            <div class="sm:col-span-3">
                <dt class="text-xs text-gray-500">Tavsif</dt>
                <dd class="mt-1 text-gray-700 dark:text-gray-300">{{ $reel->description }}</dd>
            </div>
        @endif
    </dl>
</div>

{{-- Video Items List --}}
<div class="card p-5 mb-6">
    <h2 class="text-lg font-bold mb-4">Video elementlar</h2>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tartib</th>
                        <th>Video 720p</th>
                        <th>Video 480p</th>
                        <th>Video 360p</th>
                        <th class="text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reel->items->sortBy('order') as $item)
                        <tr>
                            <td class="text-gray-500 text-sm">{{ $item->id }}</td>
                            <td>{{ $item->order }}</td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="{{ $item->video_720p }}">
                                {{ $item->video_720p ?: '—' }}
                            </td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="{{ $item->video_480p }}">
                                {{ $item->video_480p ?: '—' }}
                            </td>
                            <td class="text-xs font-mono max-w-xs truncate text-gray-600 dark:text-gray-400" title="{{ $item->video_360p }}">
                                {{ $item->video_360p ?: '—' }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end">
                                    <form method="POST" action="{{ route('admin.reels.items.destroy', [$reel, $item]) }}" onsubmit="return confirm('Bu video elementni o\'chirishga ishonchingiz komilmi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost p-2 rounded-lg text-red-500 hover:text-red-700" title="O'chirish">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday video element qo'shilmagan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add New Item Form --}}
<div class="card p-5">
    <h2 class="text-lg font-bold mb-4">Yangi video qo'shish</h2>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.reels.items.store', $reel) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 720p <span class="text-red-500">*</span></label>
            <input name="video_720p" type="file" class="input" accept="video/*" required>
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 480p <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="video_480p" type="file" class="input" accept="video/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Video 360p <span class="text-gray-400">(ixtiyoriy)</span></label>
            <input name="video_360p" type="file" class="input" accept="video/*">
        </div>

        <div>
            <label class="text-xs text-gray-500 mb-1 block">Tartib raqami</label>
            <input name="order" type="number" class="input" min="0" value="{{ old('order', $reel->items->count() + 1) }}" placeholder="Tartib raqami">
        </div>

        <div class="md:col-span-2 flex justify-end pt-2 border-t border-gray-100 dark:border-white/10">
            <button type="submit" class="btn btn-primary flex items-center gap-2">
                <i data-lucide="upload" class="w-4 h-4"></i> Yuklash va qo'shish
            </button>
        </div>
    </form>
</div>
@endsection
