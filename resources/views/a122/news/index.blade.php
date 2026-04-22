@extends('a122.layouts.admin')
@section('title', 'Yangiliklar')
@section('page-title', 'Bozor yangiliklari')

@section('content')
<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Bozor yangiliklari</div>
        <div class="a122-index-header__meta">{{ $news->total() }} ta yangilik topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Sarlavha, do‘kon yoki mahsulot bo‘yicha qidiring">
        </form>
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Yangi yangilik
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

{{-- Tabs --}}
<div class="flex items-center gap-2 mb-4 flex-wrap">
    @php
        $currentTab = request('tab', 'all');
        $tabs = [
            'all'    => ['label' => 'Barchasi',   'count' => $counts['all'] ?? 0],
            'active' => ['label' => 'Faol',        'count' => $counts['active'] ?? 0],
            'news'   => ['label' => 'Yangiliklar', 'count' => $counts['news'] ?? 0],
        ];
    @endphp
    @foreach($tabs as $key => $tab)
        <a
            href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}"
            class="btn {{ $currentTab === $key ? 'btn-primary' : 'btn-secondary' }} flex items-center gap-2 text-sm"
        >
            {{ $tab['label'] }}
            <span class="badge {{ $currentTab === $key ? 'badge-info' : 'badge-muted' }}">{{ $tab['count'] }}</span>
        </a>
    @endforeach
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="tbl" data-index-grid>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sarlavha</th>
                    <th>Harakat</th>
                    <th>Holat</th>
                    <th>Yaratilgan</th>
                    <th class="text-right">Amallar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($news as $item)
                    <tr>
                        <td class="text-gray-500 text-sm">{{ $item->id }}</td>
                        <td class="font-semibold max-w-xs truncate">{{ $item->title }}</td>
                        <td>
                            @if($item->action === 'news')
                                <span class="badge badge-info">Yangilik</span>
                            @elseif($item->action === 'to_shop')
                                <span class="badge badge-warning">Do'konga</span>
                            @elseif($item->action === 'to_product')
                                <span class="badge badge-muted">Mahsulotga</span>
                            @else
                                <span class="badge badge-muted">{{ $item->action }}</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status === 'active' || $item->status == 1 || $item->status === true)
                                <span class="badge badge-success">Faol</span>
                            @else
                                <span class="badge badge-danger">Yashirin</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500">
                            {{ $item->created_at ? $item->created_at->format('d.m.Y H:i') : '—' }}
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.news.show', $item) }}" class="btn-ghost p-2 rounded-lg" title="Ko'rish">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.news.edit', $item) }}" class="btn-ghost p-2 rounded-lg" title="Tahrirlash">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.news.toggle', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="btn-ghost p-2 rounded-lg"
                                        title="{{ ($item->status === 'active' || $item->status == 1) ? 'Yashirish' : 'Faollashtirish' }}"
                                    >
                                        @if($item->status === 'active' || $item->status == 1)
                                            <i data-lucide="eye-off" class="w-4 h-4 text-yellow-500"></i>
                                        @else
                                            <i data-lucide="eye" class="w-4 h-4 text-green-500"></i>
                                        @endif
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.news.destroy', $item) }}" onsubmit="return confirm('Bu yangilikni o\'chirishga ishonchingiz komilmi?')">
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
                        <td colspan="6" class="text-center text-gray-400 py-8">Hech qanday yangilik topilmadi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($news->hasPages())
    <div class="mt-4">{{ $news->links('a122.partials.pagination') }}</div>
@endif
@endsection
