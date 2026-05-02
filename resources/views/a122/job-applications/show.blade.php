@extends('a122.layouts.admin')
@section('title', 'Karyera #'.$careerApplication->id)
@section('page-title', 'Karyera #'.$careerApplication->id)

@section('content')
@php
    $messageCount = $careerApplication->messages->count();
    $applicationTypeLabel = $careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Ochiq murojaat' : 'Vakansiya arizasi';
@endphp
<x-a122.page-header back-href="{{ route('admin.job-applications.index') }}">
    <x-slot name="heading">Ariza #{{ $careerApplication->id }}</x-slot>
    <x-slot name="meta">{{ $careerApplication->created_at?->format('d.m.Y H:i') }} · {{ $applicationTypeLabel }}</x-slot>
    <x-slot name="actions">
        @if($careerApplication->cv_path)
            <a href="{{ route('admin.job-applications.cv', $careerApplication) }}" class="btn-p ghost">
                <i class="bi bi-file-earmark-arrow-down"></i> CV yuklab olish
            </a>
        @endif
    </x-slot>
</x-a122.page-header>

<section class="a122-section mb-4">
    <div class="a122-section-body">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
                <div class="metric-label">Ariza turi</div>
                <div class="metric-value text-xl">{{ $applicationTypeLabel }}</div>
                <div class="metric-meta">Kanal tipi</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Status</div>
                <div class="metric-value text-xl">{{ $statuses[$careerApplication->status]['label'] ?? $careerApplication->status }}</div>
                <div class="metric-meta">Joriy bosqich</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Xabarlar</div>
                <div class="metric-value text-xl">{{ number_format($messageCount) }}</div>
                <div class="metric-meta">Ichki tarix</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">CV</div>
                <div class="metric-value text-xl">{{ $careerApplication->cv_path ? 'Bor' : 'Yo‘q' }}</div>
                <div class="metric-meta">Fayl mavjudligi</div>
            </div>
        </div>
    </div>
</section>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="lg:col-span-2 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Nomzod', $careerApplication->full_name, 'user-round'],
                ['Email', $careerApplication->email, 'mail'],
                ['Telegram', $careerApplication->telegram_username ? '@'.$careerApplication->telegram_username : '—', 'send'],
                ['Holat', ($statuses[$careerApplication->status]['label'] ?? $careerApplication->status), 'badge-check'],
            ] as [$label, $value, $icon])
                <div class="p-card">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--p-soft)] text-[var(--p-accent)]">
                            <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-[var(--p-hint)]">{{ $label }}</div>
                            <div class="truncate text-sm font-medium text-[var(--p-text)]">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Nomzod</div></div>
            <div class="dash-card-body space-y-2 p-body-relaxed text-sm">
                <div><span class="text-[var(--p-hint)]">Ism:</span> <strong>{{ $careerApplication->full_name }}</strong></div>
                <div><span class="text-[var(--p-hint)]">Email:</span> {{ $careerApplication->email }}</div>
                <div><span class="text-[var(--p-hint)]">Telegram:</span>
                    @if($careerApplication->telegram_username)
                        <a href="https://t.me/{{ $careerApplication->telegram_username }}" target="_blank" rel="noopener" class="text-[var(--p-accent)]">@{{ $careerApplication->telegram_username }}</a>
                    @else — @endif
                </div>
                <div><span class="text-[var(--p-hint)]">Turi:</span>
                    {{ $careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Ochiq murojaat' : 'Vakansiya' }}
                </div>
                @if($careerApplication->vacancy)
                    <div><span class="text-[var(--p-hint)]">Lavozim:</span> {{ $careerApplication->vacancy->title }}</div>
                @endif
            </div>
        </div>

        @if($careerApplication->cover_message)
            <div class="p-card">
                <div class="dash-card-head"><div class="dash-card-title">{{ $careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat matni' : 'Qisqa xat / izoh' }}</div></div>
                <div class="dash-card-body">
                    <div class="p-quote-block">{{ $careerApplication->cover_message }}</div>
                </div>
            </div>
        @endif

        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Xabarlar</div></div>
            <div class="dash-card-body space-y-3">
                @foreach($careerApplication->messages as $msg)
                    <div class="p-msg-block">
                        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-[var(--p-hint)]">
                            <span class="s-pill {{ $msg->sender === \App\Models\CareerApplicationMessage::SENDER_ADMIN ? 'accent' : 'muted' }}">
                                {{ $msg->sender === \App\Models\CareerApplicationMessage::SENDER_ADMIN ? 'Admin' : 'Tizim' }}
                            </span>
                            @if($msg->admin)
                                <span>{{ $msg->admin->name ?? $msg->admin->email ?? 'Admin #'.$msg->admin_id }}</span>
                            @endif
                            <span>{{ $msg->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="p-msg-text">{{ $msg->body }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Nomzodga email yuborish</div></div>
            <div class="dash-card-body">
                <p class="mb-2 text-xs text-[var(--p-hint)]">Xabar <strong>{{ config('mail.from.address') }}</strong> manzilidan <strong>{{ $careerApplication->email }}</strong> ga yuboriladi (Reply-To: {{ config('mail.reply_to.address') }}).</p>
                <form method="POST" action="{{ route('admin.job-applications.reply', $careerApplication) }}">
                    @csrf
                    <textarea name="body" class="p-form-control mb-2" rows="6" required minlength="5" maxlength="12000" placeholder="Javob matni…">{{ old('body') }}</textarea>
                    <button type="submit" class="btn-p primary"><i class="bi bi-send"></i> Yuborish</button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Holat</div></div>
            <div class="dash-card-body">
                @php $st = $statuses[$careerApplication->status] ?? ['label' => $careerApplication->status, 'class' => 'ob-p']; @endphp
                <span class="o-badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                <form method="POST" action="{{ route('admin.job-applications.status', $careerApplication) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <label class="p-label text-xs text-[var(--p-hint)]">O‘zgartirish</label>
                    <select name="status" class="p-form-control mt-1" onchange="this.form.submit()">
                        @foreach($statuses as $key => $s)
                            <option value="{{ $key }}" {{ $careerApplication->status === $key ? 'selected' : '' }}>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        @if($careerApplication->read_at)
            <div class="p-card">
                <div class="dash-card-head"><div class="dash-card-title">O‘qilgan</div></div>
                <div class="dash-card-body text-xs text-[var(--p-hint)]">{{ $careerApplication->read_at->format('d.m.Y H:i') }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
