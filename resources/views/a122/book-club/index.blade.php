@extends('a122.layouts.admin')
@section('title', 'Book Club')
@section('page-title', 'Book Club postlari')

@section('content')
<div x-data="{
  view: localStorage.getItem('a122-book-club-view') || 'grid',
  setView(next) {
    this.view = next;
    localStorage.setItem('a122-book-club-view', next);
  }
}">

<x-a122.page-header>
  <x-slot name="heading">Book Club</x-slot>
  <x-slot name="meta">Foydalanuvchilar postlari va repostlari</x-slot>
  <x-slot name="actions">
    @php
      try {
        $ugcPending = \App\Models\BookClubComment::query()
          ->where('kangaroo_ugc_status', 'pending_admin')
          ->whereNull('parent_id')
          ->count()
          + \App\Models\BookClub::query()
            ->where('is_deleted', false)
            ->where('kangaroo_post_ugc_status', 'pending_admin')
            ->count();
      } catch (\Exception $e) {
        $ugcPending = 0;
      }
    @endphp
    <a href="{{ route('admin.book-club.moderation-queue') }}" class="btn-p ghost">
      <i class="bi bi-shield-exclamation"></i> UGC navbati
      @if($ugcPending > 0)
        <span class="tab-badge" style="margin-left:6px">{{ $ugcPending }}</span>
      @endif
    </a>
  </x-slot>
</x-a122.page-header>

<div class="a122-stat-grid mb-4 fade-up">
  @foreach([
    [$counts['all'] ?? 0, 'Jami postlar', 'accent', 'bi-chat-square-text'],
    [$counts['posts'] ?? 0, 'Asl postlar', 'info', 'bi-pencil-square'],
    [$counts['reposts'] ?? 0, 'Repostlar', 'warning', 'bi-arrow-repeat'],
    [$ugcPending ?? 0, 'UGC navbat', 'success', 'bi-shield-exclamation'],
  ] as [$value, $label, $tone, $icon])
    <div class="a122-stat-tile">
      <div class="a122-stat-tile__icon" style="background:var(--p-{{ $tone }}-d,var(--p-elevated));color:var(--p-{{ $tone }})">
        <i class="bi {{ $icon }}"></i>
      </div>
      <div>
        <div class="a122-stat-tile__value">{{ $value }}</div>
        <div class="a122-stat-tile__label">{{ $label }}</div>
      </div>
    </div>
  @endforeach
</div>

<div class="a122-index-header mb-3 fade-up">
  <div>
    <div class="a122-index-header__title">Book Club oqimi</div>
    <div class="a122-index-header__meta">{{ $posts->total() }} ta post ko'rinmoqda</div>
  </div>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="index-table-segment" role="tablist" aria-label="Ko‘rinish">
      <button type="button" @click="setView('list')" :class="{ 'is-active': view === 'list' }">
        <i class="bi bi-list-ul"></i>
        <span>List</span>
      </button>
      <button type="button" @click="setView('grid')" :class="{ 'is-active': view === 'grid' }">
        <i class="bi bi-grid-3x3-gap"></i>
        <span>Grid</span>
      </button>
    </div>
  </div>
</div>

<div class="a122-section fade-up">
  <div class="a122-section-head">
    <div>
      <div class="a122-section-head__title">Postlar oqimi</div>
      <div class="a122-section-head__meta">List yoki grid ko‘rinishda moderatsiya qilish va tezkor boshqarish mumkin.</div>
    </div>
  </div>
  <div class="a122-section-body">
@forelse($posts as $post)
  @if($loop->first)
  <div class="grid gap-3"
       :class="view === 'grid' ? 'grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3' : 'grid-cols-1'">
  @endif

  <div class="fade-up">
    @include('a122.book-club._post-card', ['post' => $post, 'showUser' => true])
  </div>

  @if($loop->last)</div>@endif
@empty
<div class="p-card fade-up" style="text-align:center;padding:50px;color:var(--p-hint)">
  <i class="bi bi-chat-square-text" style="font-size:36px;display:block;margin-bottom:12px"></i>
  Postlar topilmadi
</div>
@endforelse
</div>
</div>

<div class="mt-3">
  {{ $posts->links('a122.partials.pagination') }}
</div>

</div>
@endsection
