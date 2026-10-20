@extends('kitobchi-id.layout')
@section('title', 'Ruxsat berish — Kitobchi ID')

@section('content')
    @include('kitobchi-id._app')

    <h1>{{ $client->name }} quyidagilarni so'ramoqda</h1>

    <div class="account">
        <span>Akkaunt: <b style="color:inherit">{{ trim(($user->name ?? '').' '.($user->lastname ?? '')) ?: 'Kitobchi foydalanuvchisi' }}</b> · {{ $maskedPhone }}</span>
        <form method="post" action="{{ route('kitobchi-id.login.switch') }}">
            @csrf
            <input type="hidden" name="rid" value="{{ $rid }}">
            <button class="btn-link" type="submit">Boshqa akkaunt</button>
        </form>
    </div>

    <ul class="scopes">
        <li>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            <span>Sizni Kitobchi ID orqali tanish (shaxsiy identifikator)</span>
        </li>
        @foreach ($scopes as $s)
            <li>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <span>{{ $s['label'] }}</span>
            </li>
        @endforeach
    </ul>

    <p style="font-size:13px">Ruxsat berganingizdan so'ng siz <b style="color:inherit">{{ $redirectHost }}</b> manziliga qaytarilasiz. Kitobchi parolingiz, to'lov kartalaringiz va buyurtmalaringiz ilovaga berilmaydi. Ruxsatni istalgan vaqtda bekor qilishingiz mumkin.</p>

    <form method="post" action="{{ route('kitobchi-id.consent') }}" class="grid2">
        @csrf
        <input type="hidden" name="rid" value="{{ $rid }}">
        <button class="btn btn-ghost" type="submit" name="decision" value="deny">Rad etish</button>
        <button class="btn btn-primary" type="submit" name="decision" value="allow">Ruxsat berish</button>
    </form>
@endsection
