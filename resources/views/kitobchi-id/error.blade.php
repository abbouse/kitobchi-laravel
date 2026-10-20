@extends('kitobchi-id.layout')
@section('title', 'Xatolik — Kitobchi ID')

@section('content')
    <h1>Kirishni davom ettirib bo'lmadi</h1>
    <div class="err" role="alert">{{ $message }}</div>
    <p style="font-size:13px">Xato kodi: <code>{{ $code }}</code>. Muammo takrorlansa, ilova ishlab chiquvchisiga yoki Kitobchi qo'llab-quvvatlash xizmatiga (Telegram: @kitobchi_support) murojaat qiling.</p>
    <a class="btn btn-ghost" href="{{ url('/') }}">Kitobchi bosh sahifasi</a>
@endsection
