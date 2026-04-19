@extends('panel.layouts.panel')
@section('title', 'Vakansiyalar')
@section('page-title', 'Vakansiyalar')

@section('content')
<x-panel.page-header>
    <x-slot name="heading">Vakansiyalar</x-slot>
    <x-slot name="meta">Ommaviy sahifa: <a href="{{ route('careers.index') }}" target="_blank" rel="noopener" class="text-[var(--p-accent)]">/careers</a></x-slot>
    <x-slot name="actions">
        <a href="{{ route('panel.vacancies.create') }}" class="btn-p primary">
            <i class="bi bi-plus-lg"></i> Yangi vakansiya
        </a>
    </x-slot>
</x-panel.page-header>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">
        {{ session('success') }}
    </div>
@endif

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table">
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
                                <a href="{{ route('panel.vacancies.edit', $row) }}" class="btn-p ghost sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('panel.vacancies.toggle', $row) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-p ghost sm" title="Ko‘rinishni almashtirish">
                                        <i class="bi bi-{{ $row->is_active ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('panel.vacancies.destroy', $row) }}"
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
@endsection
