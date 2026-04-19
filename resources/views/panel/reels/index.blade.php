@extends('panel.layouts.panel')
@section('title', 'Reels')
@section('page-title', 'Reels')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Reels</x-slot>
  <x-slot name="meta">Video kolleksiyalar boshqaruvi</x-slot>
  <x-slot name="actions">
    <a href="{{ route('panel.reels.create') }}" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi Reel
      </a>
  </x-slot>
</x-panel.page-header>


<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>Tartib</th>
          <th>Sarlavha</th>
          <th>Tavsif</th>
          <th>Videolar</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($reels as $reel)
        <tr>
          <td>
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;
                         color:var(--p-accent);font-weight:600">
              {{ $reel->order }}
            </span>
          </td>

          <td>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">
              {{ $reel->title }}
            </div>
          </td>

          <td style="max-width:200px">
            <div style="font-size:12px;color:var(--p-muted);white-space:nowrap;
                        overflow:hidden;text-overflow:ellipsis">
              {{ $reel->description ?: '—' }}
            </div>
          </td>

          <td>
            <span class="s-pill {{ $reel->items_count > 0 ? 'accent' : 'muted' }}"
                  style="font-size:11px">
              <i class="bi bi-play-circle mr-1"></i>
              {{ $reel->items_count }} ta video
            </span>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $reel->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="flex gap-1">
              <a href="{{ route('panel.reels.show', $reel) }}"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('panel.reels.edit', $reel) }}"
                 class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST"
                    action="{{ route('panel.reels.destroy', $reel) }}"
                    onsubmit="return confirm('Reel va barcha videolari o\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="p-empty-cell">
            <i class="bi bi-collection-play"
               style="font-size:32px;display:block;margin-bottom:10px"></i>
            Reels topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($reels->hasPages())
  <div class="p-card-footer">
    <div class="p-card-footer-meta">
      {{ $reels->firstItem() }}–{{ $reels->lastItem() }} / {{ $reels->total() }}
    </div>
    {{ $reels->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection