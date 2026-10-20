@extends('layouts.marketplace')

@section('content')
{{-- Huquqiy sahifalar Nuxt'dagi PolicyPage bilan bir xil ko'rinishda (kitobchi-marketplace.css → .kl-*) --}}
<main class="kl-page">
    <div class="kl-wrap">
        @yield('legal_content', $__env->yieldContent('content'))
    </div>
</main>
@endsection
