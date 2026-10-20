@extends('kitobchi-id.layout')
@section('title', 'Kitobchi ID orqali kirish')

@section('content')
    @include('kitobchi-id._app')

    @if ($step === 'code')
        <h1>Kodni kiriting</h1>
        <p>{{ \App\Http\Controllers\KitobchiId\AuthorizeController::maskPhone((string) $phone) }} raqamiga SMS orqali 6 xonali kod yuborildi.</p>

        @if ($error)<div class="err" role="alert">{{ $error }}</div>@endif

        <form method="post" action="{{ route('kitobchi-id.login.verify') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="rid" value="{{ $rid }}">
            <label for="code">Tasdiqlash kodi</label>
            <input id="code" class="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
            <button class="btn btn-primary" type="submit">Tasdiqlash</button>
        </form>

        <div class="row">
            <form method="post" action="{{ route('kitobchi-id.login.send') }}">
                @csrf
                <input type="hidden" name="rid" value="{{ $rid }}">
                <input type="hidden" name="phone" value="{{ $phone }}">
                <button class="btn-link" id="resend" type="submit" data-wait="{{ $resend }}">Kodni qayta yuborish</button>
            </form>
            <form method="post" action="{{ route('kitobchi-id.login.switch') }}">
                @csrf
                <input type="hidden" name="rid" value="{{ $rid }}">
                <button class="btn-link" type="submit">Raqamni o'zgartirish</button>
            </form>
        </div>
        <script>
        (function(){var b=document.getElementById('resend');if(!b)return;var t=parseInt(b.dataset.wait,10)||60,l=b.textContent;
        b.disabled=true;var i=setInterval(function(){t--;b.textContent=l+' ('+t+')';if(t<=0){clearInterval(i);b.disabled=false;b.textContent=l;}},1000);})();
        </script>
    @else
        <h1>Kitobchi ID orqali kirish</h1>
        <p><b style="color:inherit">{{ $client->name }}</b> ilovasiga Kitobchi akkauntingiz bilan kiring. Akkauntingiz bo'lmasa — avtomatik yaratiladi.</p>

        @if ($error)<div class="err" role="alert">{{ $error }}</div>@endif

        <form method="post" action="{{ route('kitobchi-id.login.send') }}">
            @csrf
            <input type="hidden" name="rid" value="{{ $rid }}">
            <label for="phone">Telefon raqam</label>
            <input id="phone" type="tel" name="phone" inputmode="tel" autocomplete="tel" placeholder="+998 90 123 45 67" value="{{ $phone ? '+'.$phone : '+998 ' }}" required autofocus>
            <button class="btn btn-primary" type="submit">Kod olish</button>
        </form>
        <p style="font-size:13px;margin:14px 0 0">Davom etish orqali siz Kitobchi <a href="{{ url('/legal/foydalanish-shartlari') }}" target="_blank" rel="noopener">Foydalanish shartlari</a>ga rozilik bildirasiz.</p>
    @endif
@endsection
