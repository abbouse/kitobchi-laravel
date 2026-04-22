@extends('a122.layouts.admin')
@section('title', 'Mystery Box tariflar')
@section('page-title', 'Mystery Box')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">Mystery Box tariflar</x-slot>
  <x-slot name="meta">Foydalanuvchilarga ko'rsatiladigan obuna rejalari</x-slot>
  <x-slot name="actions">
    <a href="{{ route('admin.mystery-box.subscriptions') }}" class="btn-p ghost">
        <i class="bi bi-list-ul"></i> Obunalar
      </a>
  </x-slot>
</x-a122.page-header>


<div class="grid grid-cols-1 xl:grid-cols-12 gap-3 items-start">

  {{-- ── Mavjud tariflar ──────────────────────────────────── --}}
  <div class="xl:col-span-8">
    @forelse($plans as $plan)
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div>
          <div class="p-card-title">{{ $plan->name_uz }}</div>
          @if($plan->name_ru)
          <div style="font-size:11px;color:var(--p-hint)">{{ $plan->name_ru }}</div>
          @endif
        </div>
        <div class="flex items-center gap-2">
          <span class="s-pill {{ $plan->is_active ? 'success' : 'muted' }}" style="font-size:10px">
            {{ $plan->is_active ? 'Faol' : 'Nofaol' }}
          </span>
          <span class="s-pill muted" style="font-size:10px">
            {{ $plan->subscriptions_count }} ta obuna
          </span>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.mystery-box.plans.update', $plan) }}">
        @csrf @method('PUT')
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Nomi (UZ) *</label>
              <input type="text" name="name_uz" class="p-form-control"
                     value="{{ $plan->name_uz }}" required>
            </div>
            <div class="">
              <label class="p-form-label">Nomi (RU)</label>
              <input type="text" name="name_ru" class="p-form-control"
                     value="{{ $plan->name_ru }}">
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Muddat (oy)</label>
              <input type="number" class="p-form-control"
                     value="{{ $plan->months }}" disabled
                     style="background:var(--p-elevated);cursor:not-allowed">
              <div style="font-size:10px;color:var(--p-hint);margin-top:3px">
                O'zgartirib bo'lmaydi
              </div>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Har oyda (kitob) *</label>
              <input type="number" name="books_per_month" class="p-form-control"
                     value="{{ $plan->books_per_month }}" min="1" max="10" required>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Narxi (UZS) *</label>
              <input type="number" name="price_uzs" class="p-form-control"
                     value="{{ $plan->price_uzs }}" min="1000" required>
              <div style="font-size:10px;color:var(--p-hint);margin-top:3px">
                Oyiga: {{ number_format((int)($plan->price_uzs / $plan->months)) }} UZS
              </div>
            </div>
            <div class="md:col-span-3">
              <label class="p-form-label">Tartib</label>
              <input type="number" name="sort_order" class="p-form-control"
                     value="{{ $plan->sort_order }}" min="0">
            </div>
            <div class="">
              <label class="p-form-label">Tavsif</label>
              <textarea name="description_uz" class="p-form-control"
                        rows="2">{{ $plan->description_uz }}</textarea>
            </div>
            <div class="">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ $plan->is_active ? 'checked' : '' }}
                       style="width:16px;height:16px;accent-color:var(--p-accent)">
                <span style="font-size:13px;color:var(--p-text)">Faol (foydalanuvchilarga ko'rinadi)</span>
              </label>
            </div>
          </div>

          <div class="flex gap-2 mt-3">
            <button type="submit" class="btn-p primary">
              <i class="bi bi-check-lg"></i> Saqlash
            </button>
            @if($plan->subscriptions_count === 0)
            <button type="button"
                    onclick="if(confirm('O\'chirilsinmi?')) document.getElementById('del{{ $plan->id }}').submit()"
                    class="btn-p danger ghost sm">
              <i class="bi bi-trash"></i>
            </button>
            @endif
          </div>
        </div>
      </form>

      @if($plan->subscriptions_count === 0)
      <form id="del{{ $plan->id }}" method="POST"
            action="{{ route('admin.mystery-box.plans.destroy', $plan) }}" style="display:none">
        @csrf @method('DELETE')
      </form>
      @endif
    </div>
    @empty
    <div class="p-card fade-up" style="text-align:center;padding:40px;color:var(--p-hint)">
      <i class="bi bi-box" style="font-size:32px;display:block;margin-bottom:8px"></i>
      Hali tariflar yo'q. O'ngdagi formadan birinchi tarifni yarating.
    </div>
    @endforelse
  </div>

  {{-- ── Yangi tarif ──────────────────────────────────────── --}}
  <div class="xl:col-span-4">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-plus-lg mr-1" style="color:var(--p-accent)"></i>
          Yangi tarif
        </div>
      </div>
      <form method="POST" action="{{ route('admin.mystery-box.plans.store') }}">
        @csrf
        <div style="padding:0 18px 18px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="">
              <label class="p-form-label">Nomi (UZ) *</label>
              <input type="text" name="name_uz" class="p-form-control"
                     placeholder="Masalan: 3 oylik obuna" required>
            </div>
            <div class="">
              <label class="p-form-label">Nomi (RU)</label>
              <input type="text" name="name_ru" class="p-form-control"
                     placeholder="Masalan: Подписка на 3 месяца">
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Muddat (oy) *</label>
              <select name="months" class="p-form-control" required>
                <option value="1">1 oy</option>
                <option value="3">3 oy</option>
                <option value="6">6 oy</option>
                <option value="12">12 oy</option>
              </select>
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Kitob/oy *</label>
              <input type="number" name="books_per_month" class="p-form-control"
                     value="2" min="1" max="10" required>
            </div>
            <div class="">
              <label class="p-form-label">Narxi (UZS) *</label>
              <input type="number" name="price_uzs" class="p-form-control"
                     placeholder="350000" min="1000" required>
            </div>
            <div class="w-1/2">
              <label class="p-form-label">Tartib</label>
              <input type="number" name="sort_order" class="p-form-control"
                     value="{{ $plans->max('sort_order') + 1 }}">
            </div>
            <div class="">
              <label class="p-form-label">Tavsif</label>
              <textarea name="description_uz" class="p-form-control"
                        rows="2" placeholder="Qisqa tavsif..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn-p primary mt-3" style="width:100%;justify-content:center">
            <i class="bi bi-plus-lg"></i> Tarif yaratish
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

@endsection