@extends('a122.layouts.admin')
@section('title', 'Book Club')
@section('page-title', 'Book Club')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Community" title="Book Club" subtitle="{{ $posts->total() }} ta post" />

  <div class="row g-3">
    @foreach([
      [$counts['all'] ?? 0, 'Jami postlar', 'bi-chat-square-text', 'primary'],
      [$counts['posts'] ?? 0, 'Asl postlar', 'bi-pencil-square', 'info'],
      [$counts['reposts'] ?? 0, 'Repostlar', 'bi-arrow-repeat', 'warning'],
      [$posts->total() ?? 0, 'Ko‘rinmoqda', 'bi-stars', 'success'],
    ] as [$value, $label, $icon, $tone])
      <div class="col-6 col-xl-3">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="bi {{ $icon }}"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value">{{ number_format($value) }}</div>
            <div class="a122-stat-tile__label">{{ $label }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <x-admin.section-card title="Postlar oqimi" :meta="$posts->total() . ' ta yozuv'">
    @forelse($posts as $post)
      @if($loop->first)
        <div class="row g-3">
      @endif
      <div class="col-12 col-xl-6 col-xxl-4">
        @include('a122.book-club._post-card', ['post' => $post, 'showUser' => true])
      </div>
      @if($loop->last)
        </div>
      @endif
    @empty
      <div class="text-center py-5 text-secondary">Post topilmadi.</div>
    @endforelse
  </x-admin.section-card>

  @if($posts->hasPages())
    <div>{{ $posts->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
