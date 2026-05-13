@extends('a122.layouts.guest')
@section('title', 'Hub Desk')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-8">
  <div class="w-full max-w-md rounded-[28px] border border-white/60 bg-white/95 p-6 shadow-2xl">
    <div class="mb-6">
      <div class="text-sm font-semibold uppercase tracking-[0.24em] text-sky-600">Kitobchi Hub Desk</div>
      <h1 class="mt-2 text-2xl font-bold text-slate-900">Hub staff login</h1>
      <p class="mt-2 text-sm leading-6 text-slate-500">USB printer bilan ishlaydigan desk rejimi. Shu yerda label va receipt’larni brauzer orqali chop etish mumkin.</p>
    </div>

    @if($errors->any())
      <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('hubdesk.login.post') }}" class="space-y-4">
      @csrf
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Login</label>
        <input type="text" name="username" value="{{ old('username') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-sky-400" required>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Parol</label>
        <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-sky-400" required>
      </div>
      <button type="submit" class="w-full rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">Hub deskga kirish</button>
    </form>
  </div>
</div>
@endsection
