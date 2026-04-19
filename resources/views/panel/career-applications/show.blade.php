@extends('panel.layouts.panel')
@section('title', 'Karyera #'.$careerApplication->id)
@section('page-title', 'Karyera #'.$careerApplication->id)

@section('content')
<x-panel.page-header back-href="{{ route('panel.career-applications.index') }}">
    <x-slot name="heading">Ariza #{{ $careerApplication->id }}</x-slot>
    <x-slot name="meta">{{ $careerApplication->created_at?->format('d.m.Y H:i') }}</x-slot>
    <x-slot name="actions">
        @if($careerApplication->cv_path)
            <a href="{{ route('panel.career-applications.cv', $careerApplication) }}" class="btn-p ghost">
                <i class="bi bi-file-earmark-arrow-down"></i> CV yuklab olish
            </a>
        @endif
    </x-slot>
</x-panel.page-header>

@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
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
                <form method="POST" action="{{ route('panel.career-applications.reply', $careerApplication) }}">
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
                <form method="POST" action="{{ route('panel.career-applications.status', $careerApplication) }}" class="mt-3">
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
