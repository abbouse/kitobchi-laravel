@extends('panel.layouts.panel')
@section('title', 'Reklama narxlari')
@section('page-title', 'Reklama narxlari')

@section('content')
<div class="kc-page-inner w-full min-w-0">
    <x-panel.page-header back-href="{{ route('panel.seller-ads.index') }}">
  <x-slot name="heading">Reklama narxlari</x-slot>
  <x-slot name="meta">Har bir reklama turi uchun kunlik narx</x-slot>
</x-panel.page-header>


    <div class="p-card">
      <div class="dash-card-head"><div class="dash-card-title">Reklama turlari narxlari</div></div>
      <div class="dash-card-body">
        <form method="POST" action="{{ route('panel.seller-ads.settings.update') }}">
          @csrf @method('PUT')

          @foreach($settings as $i => $setting)
          <div style="background:var(--p-elevated);border-radius:10px;padding:16px;margin-bottom:12px">
            <div style="font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;margin-bottom:12px;letter-spacing:.07em">
              <i class="bi bi-megaphone mr-1"></i> {{ $setting->type }}
            </div>
            <input type="hidden" name="settings[{{ $i }}][type]" value="{{ $setting->type }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="">
                <label class="p-form-label">Kunlik narx (UZS)</label>
                <input type="number" name="settings[{{ $i }}][price]" class="p-form-control"
                       value="{{ old("settings.$i.price", $setting->price) }}" min="0" required>
              </div>
              <div class="">
                <label class="p-form-label">Viloyat (ixtiyoriy)</label>
                <input type="text" name="settings[{{ $i }}][region]" class="p-form-control"
                       value="{{ old("settings.$i.region", $setting->region) }}"
                       placeholder="Toshkent">
              </div>
            </div>
          </div>
          @endforeach

          @if($settings->isEmpty())
          <div style="text-align:center;padding:30px;color:var(--p-hint)">
            <i class="bi bi-megaphone" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Reklama turlari sozlamasi yo'q
          </div>
          @endif

          @if($settings->isNotEmpty())
          <div class="flex justify-end mt-2">
            <button class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
          </div>
          @endif
        </form>
      </div>
    </div>
</div>
@endsection