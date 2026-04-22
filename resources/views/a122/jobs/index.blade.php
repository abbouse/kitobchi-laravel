@extends('a122.layouts.admin')
@section('title', 'Vakansiyalar')
@section('page-title', 'Vakansiyalar')

@section('content')
<x-a122.page-header>
    <x-slot name="heading">Vakansiyalar</x-slot>
    <x-slot name="meta">Ommaviy sahifa: <a href="{{ route('careers.index') }}" target="_blank" rel="noopener" class="text-[var(--p-accent)]">/careers</a></x-slot>
</x-a122.page-header>

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Vakansiyalar ro‘yxati</div>
        <div class="a122-index-header__meta">{{ $vacancies->total() }} ta e’lon topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Lavozim, joylashuv yoki tur bo‘yicha qidiring">
        </form>
        <a href="{{ route('admin.jobs.create') }}" class="btn-p primary">
            <i class="bi bi-plus-lg"></i> Yangi vakansiya
        </a>
    </div>
</div>

<div class="tab-pills fade-up mb-3">
    @foreach([
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Yashirin', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
    ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
            {{ $label }} <span>{{ $count }}</span>
        </a>
    @endforeach
</div>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
        {{ session('success') }}
    </div>
@endif

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
            <thead>
                <tr>
                    <th>#</th>
                    <th class="w-10"></th>
                    <th>Nomi</th>
                    <th>Turi</th>
                    <th>Joy</th>
                    <th>Tartib</th>
                    <th>Holat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($vacancies as $row)
                    @php
                        $ic = $row->resolvedIcon();
                        $bi = match ($ic) {
                            'code' => 'code-slash',
                            'palette' => 'palette-fill',
                            'shop' => 'shop',
                            'megaphone' => 'megaphone-fill',
                            'people' => 'people-fill',
                            'chart' => 'graph-up-arrow',
                            default => 'briefcase-fill',
                        };
                    @endphp
                    <tr>
                        <td class="p-td-id">{{ $row->id }}</td>
                        <td class="text-center text-slate-400" title="{{ \App\Models\Vacancy::iconOptions()[$ic] ?? '' }}">
                            <i class="bi bi-{{ $bi }}"></i>
                        </td>
                        <td class="p-td-strong">{{ $row->title }}</td>
                        <td class="p-td-muted">{{ $row->contract_type ?: '—' }}</td>
                        <td class="p-td-muted">{{ $row->location ?: '—' }}</td>
                        <td>{{ $row->sort_order }}</td>
                        <td>
                            <span class="s-pill {{ $row->is_active ? 'success' : 'muted' }}">
                                {{ $row->is_active ? 'Faol' : 'Yashirin' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="{{ route('admin.jobs.edit', $row) }}" class="btn-p ghost sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.jobs.toggle', $row) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-p ghost sm" title="Ko‘rinishni almashtirish">
                                        <i class="bi bi-{{ $row->is_active ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.jobs.destroy', $row) }}"
                                      class="inline" onsubmit="return confirm('O‘chirilsinmi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-p ghost sm text-red-400">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-[var(--p-hint)]">
                            Hozircha vakansiya yo‘q. «Yangi vakansiya» tugmasidan qo‘shing.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($vacancies->hasPages())
    <div class="mt-4">{{ $vacancies->links('a122.partials.pagination') }}</div>
@endif
@endsection
