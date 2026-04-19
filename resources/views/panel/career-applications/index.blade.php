@extends('panel.layouts.panel')
@section('title', 'Karyera arizalari')
@section('page-title', 'Karyera arizalari')

@section('content')
<x-panel.page-header>
    <x-slot name="heading">Karyera arizalari</x-slot>
    <x-slot name="meta">Sayt /careers — vakansiya va ochiq murojaatlar</x-slot>
</x-panel.page-header>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200">{{ session('error') }}</div>
@endif

<div class="tab-pills fade-up mb-3">
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}"
       class="tab-pill {{ $tab === 'all' ? 'active' : '' }}">
        Barchasi <span class="tab-badge">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'vacancy', 'page' => 1]) }}"
       class="tab-pill {{ $tab === 'vacancy' ? 'active' : '' }}">
        Vakansiya <span class="tab-badge">{{ $counts['vacancy'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'inquiry', 'page' => 1]) }}"
       class="tab-pill {{ $tab === 'inquiry' ? 'active' : '' }}">
        Ochiq murojaat <span class="tab-badge">{{ $counts['inquiry'] }}</span>
    </a>
</div>

<div class="filter-bar mb-3">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="search" class="p-form-control p-filter-input-wide" placeholder="ID, ism, email, Telegram…"
               value="{{ request('search') }}">
        <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
        <a href="{{ route('panel.career-applications.index', ['tab' => $tab]) }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
    </form>
</div>

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Turi</th>
                    <th>Lavozim / —</th>
                    <th>Ism</th>
                    <th>Email</th>
                    <th>Telegram</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $row)
                    @php
                        $st = $statuses[$row->status] ?? ['label' => $row->status, 'class' => 'ob-p'];
                    @endphp
                    <tr>
                        <td class="p-td-id">#{{ $row->id }}</td>
                        <td>
                            <span class="s-pill {{ $row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'muted' : 'accent' }}">
                                {{ $row->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat' : 'Vakansiya' }}
                            </span>
                        </td>
                        <td class="p-td-max p-td-title">{{ $row->vacancy?->title ?? '—' }}</td>
                        <td class="p-td-strong">{{ $row->full_name }}</td>
                        <td class="p-td-muted">{{ $row->email }}</td>
                        <td class="p-td-hint">{{ $row->telegram_username ? '@'.$row->telegram_username : '—' }}</td>
                        <td><span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span></td>
                        <td class="p-td-hint">{{ $row->created_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('panel.career-applications.show', $row) }}" class="btn-p ghost sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-[var(--p-hint)]">Hozircha ariza yo‘q.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $applications->links() }}
</div>
@endsection
