@extends('a122.layouts.admin')
@section('title', 'Karyera arizalari')
@section('page-title', 'Karyera arizalari')

@section('content')
<x-a122.page-header>
    <x-slot name="heading">Karyera arizalari</x-slot>
    <x-slot name="meta">Vakansiya bo'yicha kelgan nomzodlar va umumiy murojaatlar shu yerda bir xil standartda ko'rinadi.</x-slot>
</x-a122.page-header>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
    @foreach([
        [$counts['all'], 'Jami ariza', 'accent', 'bi-briefcase'],
        [$counts['vacancy'], 'Vakansiya', 'info', 'bi-person-workspace'],
        [$counts['inquiry'], 'Ochiq murojaat', 'warning', 'bi-chat-square-text'],
        [$counts['new'], 'Yangi', 'success', 'bi-stars'],
    ] as [$value, $label, $tone, $icon])
        <div class="p-card flex items-center gap-3" style="padding:14px">
            <div style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-{{ $tone }}-d,var(--p-elevated));color:var(--p-{{ $tone }})">
                <i class="bi {{ $icon }}"></i>
            </div>
            <div>
                <div style="font-size:22px;font-weight:700;color:var(--p-text)">{{ $value }}</div>
                <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--p-hint)">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

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

<div class="a122-index-header">
    <div>
        <div class="a122-index-header__title">Arizalar ro'yxati</div>
        <div class="a122-index-header__meta">{{ $applications->total() }} ta ariza topildi</div>
    </div>
    <div class="a122-index-header__actions">
        <form method="GET" class="a122-index-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <i class="bi bi-search"></i>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="ID, ism, email yoki Telegram bo'yicha qidiring">
        </form>
        @if(request('search'))
            <a href="{{ route('admin.job-applications.index', ['tab' => $tab]) }}" class="btn-p ghost">Tozalash</a>
        @endif
    </div>
</div>

<div class="p-card p-0">
    <div class="table-responsive kc-twrap">
        <table class="p-table" data-index-grid>
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
                        <td>
                            <form method="POST" action="{{ route('admin.job-applications.status', $row) }}" class="inline-flex">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="status-select status-select--compact" onchange="this.form.submit()">
                                    @foreach($statuses as $key => $status)
                                        <option value="{{ $key }}" {{ $row->status === $key ? 'selected' : '' }}>{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="p-td-hint">{{ $row->created_at?->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.job-applications.show', $row) }}" class="btn-p ghost sm">
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
    {{ $applications->links('a122.partials.pagination') }}
</div>
@endsection
