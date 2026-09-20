@extends('layouts.marketplace')

@section('content')
<main class="max-md:pb-[71px] grow py-6 md:py-12 bg-[#F8FAFC]">
    <div class="px-4 sm:px-6 lg:px-8 w-full max-w-(--ui-container) mx-auto">
        @yield('legal_content', $__env->yieldContent('content'))
    </div>
</main>
@endsection

