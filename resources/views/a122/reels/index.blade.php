@extends('a122.layouts.admin')
@section('title', 'Reels')
@section('page-title', 'Reels')

@section('content')
<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Reels ro‘yxati</div>
        <div class="a122-index-header__meta">{{ $reels->total() }} ta reel topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Sarlavha yoki tavsif bo‘yicha qidiring">
        </form>
        <a href="{{ route('admin.reels.create') }}" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi reel
        </a>
    </div>
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

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sarlavha</th>
                    <th>Tavsif</th>
                    <th>Tartib</th>
                    <th>Videolar soni</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reels as $reel)
                    <tr>
                        <td class="text-gray-500 text-sm">{{ $reel->id }}</td>
                        <td class="font-semibold">{{ $reel->title }}</td>
                        <td class="text-sm text-gray-500 max-w-xs truncate">{{ $reel->description ?: '—' }}</td>
                        <td>{{ $reel->order }}</td>
                        <td>
                            <span class="badge badge-info">{{ $reel->items_count ?? $reel->items->count() }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.reels.show', $reel) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.reels.edit', $reel) }}" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.reels.destroy', $reel) }}" onsubmit="return confirm('Bu reelni o\'chirishga ishonchingiz komilmi?')">
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
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday reel topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($reels->hasPages())
    <div class="mt-4">{{ $reels->links('a122.partials.pagination') }}</div>
@endif
@endsection
