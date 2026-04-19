{{-- =====================================================
     book-categories/index.blade.php
===================================================== --}}
@extends('panel.layouts.panel')
@section('title', 'Kitob kategoriyalari')
@section('page-title', 'Kitob kategoriyalari')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Kitob kategoriyalari</x-slot>
  <x-slot name="meta">Jami <strong>{{ $stats['total'] }}</strong> ta &middot;
      <span style="color:var(--p-success)">{{ $stats['active'] }} ta faol</span></x-slot>
  <x-slot name="actions">
    <a href="{{ route('panel.book-categories.create') }}" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi kategoriya
      </a>
  </x-slot>
</x-panel.page-header>


{{-- Filter --}}
<div class="filter-bar mb-3">
  <form method="GET" class="flex flex-wrap gap-2 items-center">
    <input type="search" name="search" class="p-form-control" placeholder="Nom bo'yicha..."
           value="{{ request('search') }}" style="width:220px">
    <select name="status" class="p-form-control" style="width:150px">
      <option value="">Barchasi</option>
      <option value="1" {{ request('status')==='1'?'selected':'' }}>Aktiv</option>
      <option value="0" {{ request('status')==='0'?'selected':'' }}>Nofaol</option>
    </select>
    <button class="btn-p primary" type="submit"><i class="bi bi-search"></i> Izlash</button>
    <a href="{{ route('panel.book-categories.index') }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Icon</th>
          <th>Nomi (UZ)</th>
          <th>Nomi (RU)</th>
          <th>Kitoblar</th>
          <th>Status</th>
          <th>Teglar</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $cat)
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $cat->id }}</td>
          <td style="font-size:22px;text-align:center">{{ $cat->icon ?? '📚' }}</td>
          <td style="font-weight:500;color:var(--p-text)">{{ $cat->name_uz }}</td>
          <td style="color:var(--p-muted)">{{ $cat->name_ru }}</td>
          <td>
            <span class="s-pill accent">{{ number_format($cat->books_count) }} ta</span>
          </td>
          <td>
            <span class="s-pill {{ $cat->is_active ? 'success' : 'muted' }}">
              {{ $cat->is_active ? 'Aktiv' : 'Nofaol' }}
            </span>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            {{ $cat->tags?->pluck('tag_name_uz')->take(3)->implode(', ') ?: '—' }}
          </td>
          <td>
            <div class="flex gap-1">
              <a href="{{ route('panel.book-categories.edit', $cat) }}" class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST" action="{{ route('panel.book-categories.toggle', $cat) }}">
                @csrf @method('PATCH')
                <button class="btn-p ghost sm" title="{{ $cat->is_active ? 'O\'chirish' : 'Faollashtirish' }}">
                  <i class="bi bi-{{ $cat->is_active ? 'eye-slash' : 'eye' }}"></i>
                </button>
              </form>
              @if($cat->books_count == 0)
              <form method="POST" action="{{ route('panel.book-categories.destroy', $cat) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">Kategoriyalar topilmadi</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($categories->hasPages())
  <div class="p-pagination">{{ $categories->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection