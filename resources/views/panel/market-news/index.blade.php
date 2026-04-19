@extends('panel.layouts.panel')
@section('title', 'Yangiliklar')
@section('page-title', 'Market Yangiliklari')

@section('content')

<x-panel.page-header>
  <x-slot name="heading">Market Yangiliklari</x-slot>
  <x-slot name="meta">Marketpleysda ko'rinadigan bannerlar</x-slot>
  <x-slot name="actions">
    <a href="{{ route('panel.market-news.create') }}" class="btn-p primary">
        <i class="bi bi-plus-lg"></i> Yangi yangilik
      </a>
  </x-slot>
</x-panel.page-header>


{{-- Stats --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
  @foreach([
    ['Jami',    $counts['all'],    'accent',  'bi-newspaper'],
    ['Faol',    $counts['active'], 'success', 'bi-check-circle'],
    ['Yangilik',$counts['news'],   'info',    'bi-chat-text'],
  ] as [$l,$v,$c,$i])
  <div class="col-4">
    <div class="p-card flex items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-{{ $c }}-d,var(--p-elevated));color:var(--p-{{ $c }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $i }}"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
          {{ $v }}
        </div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          {{ $l }}
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Rasm</th>
          <th>Sarlavha</th>
          <th>Joylashuv</th>
          <th>Action</th>
          <th>Manzil</th>
          <th>Status</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($news as $item)
        @php
          $actionColor = match($item->action){
            'to_shop'    => 'warning',
            'to_product' => 'info',
            default      => 'muted',
          };
          $actionIcon = match($item->action){
            'to_shop'    => 'bi-shop-window',
            'to_product' => 'bi-book',
            default      => 'bi-newspaper',
          };
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-size:12px">
            #{{ $item->id }}
          </td>

          <td>
            @if($item->imgUrl)
            <div style="width:52px;height:36px;border-radius:6px;overflow:hidden;
                        background:var(--p-elevated)">
              <img src="{{ asset('storage/'.$item->imgUrl) }}"
                   style="width:100%;height:100%;object-fit:cover">
            </div>
            @else
            <div style="width:52px;height:36px;border-radius:6px;background:var(--p-elevated);
                        display:flex;align-items:center;justify-content:center">
              <i class="bi bi-image" style="color:var(--p-hint);font-size:14px"></i>
            </div>
            @endif
          </td>

          <td>
            <div style="font-size:13px;font-weight:500;color:var(--p-text);
                        max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ $item->title }}
            </div>
          </td>

          <td>
            <span class="s-pill muted" style="font-size:10px">
              {{ $item->align === 'top' ? '⬆ Yuqori' : '↔ O\'rta' }}
            </span>
          </td>

          <td>
            <span class="s-pill {{ $actionColor }}" style="font-size:10px">
              <i class="bi {{ $actionIcon }} mr-1"></i>
              {{ $item->action_label }}
            </span>
          </td>

          <td>
            @if($item->action === 'to_shop' && $item->seller)
              <a href="{{ route('panel.sellers.show', $item->action_id) }}"
                 style="font-size:12px;color:var(--p-warning);text-decoration:none">
                {{ $item->seller->shop_name }}
              </a>
            @elseif($item->action === 'to_product' && $item->book)
              <a href="{{ route('panel.books.show', $item->action_id) }}"
                 style="font-size:12px;color:var(--p-info);text-decoration:none">
                {{ Str::limit($item->book->name, 24) }}
              </a>
            @elseif($item->action_id)
              <span style="font-size:11px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                #{{ $item->action_id }}
              </span>
            @else
              <span style="color:var(--p-hint)">—</span>
            @endif
          </td>

          <td>
            <form method="POST"
                  action="{{ route('panel.market-news.toggle', $item) }}"
                  style="display:inline">
              @csrf @method('PATCH')
              <button class="s-pill {{ $item->status ? 'success' : 'danger' }}"
                      style="font-size:10px;border:none;cursor:pointer;padding:3px 10px">
                {{ $item->status ? '● Faol' : '○ Nofaol' }}
              </button>
            </form>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $item->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="flex gap-1">
              <a href="{{ route('panel.market-news.show', $item) }}"
                 class="btn-p ghost sm">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('panel.market-news.edit', $item) }}"
                 class="btn-p ghost sm">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="POST"
                    action="{{ route('panel.market-news.destroy', $item) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
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
          <td colspan="9" class="p-empty-cell">
            <i class="bi bi-newspaper" style="font-size:32px;display:block;margin-bottom:8px"></i>
            Yangiliklar topilmadi
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($news->hasPages())
  <div class="flex items-center justify-between px-3 py-2"
       style="border-top:1px solid var(--p-border)">
    <div style="font-size:12px;color:var(--p-hint)">
      {{ $news->firstItem() }}–{{ $news->lastItem() }} / {{ $news->total() }}
    </div>
    {{ $news->links('panel.partials.pagination') }}
  </div>
  @endif
</div>

@endsection