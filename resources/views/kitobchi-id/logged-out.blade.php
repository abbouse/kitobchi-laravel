@extends('kitobchi-id.layout')
@section('title', 'Chiqdingiz — Kitobchi ID')

@section('content')
    <h1>Kitobchi ID'dan chiqdingiz</h1>
    <p>Ushbu brauzerdagi Kitobchi ID sessiyasi yakunlandi. Ilovalarga keyingi kirishda telefon raqamingiz qayta tasdiqlanadi.</p>
    <a class="btn btn-ghost" href="{{ url('/') }}">Kitobchi bosh sahifasi</a>
@endsection
