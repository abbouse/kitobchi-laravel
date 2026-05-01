@extends('a122.layouts.admin')
@section('title', 'Book Club · UGC navbati')
@section('page-title', 'Kangaroo: admin navbati')

@section('content')

<x-a122.page-header back-href="{{ route('admin.book-club.index') }}">
  <x-slot name="heading">UGC navbati</x-slot>
  <x-slot name="meta">
    Kangaroo past ishonch yoki bahosiz qoldirgan post va izohlarni 1–5 yulduz bilan baholang.
  </x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 gap-3">

  <div class="a122-section fade-up">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Post matni (kutilmoqda)</div>
        <div class="a122-section-head__meta">{{ $pendingPosts->count() }} ta post admin bahosini kutmoqda.</div>
      </div>
    </div>
    <div class="a122-section-body p-0">
      @forelse($pendingPosts as $row)
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
            <div>
              <a href="{{ route('admin.book-club.show', $row) }}" class="font-semibold" style="color:var(--p-text);text-decoration:none">
                Post #{{ $row->id }}
              </a>
              <span style="font-size:12px;color:var(--p-hint);margin-left:8px">
                {{ $row->created_at?->format('d.m.Y H:i') }}
              </span>
              @if($row->user)
                <div style="font-size:12px;color:var(--p-muted);margin-top:4px">
                  {{ $row->user->name }} {{ $row->user->lastname }}
                </div>
              @endif
            </div>
            <span class="btn-p ghost sm" style="pointer-events:none;border-color:var(--p-warning);color:var(--p-warning)">
              <i class="bi bi-hourglass-split"></i> pending_admin
            </span>
          </div>
          @if($row->text)
            <p style="font-size:13px;color:var(--p-text);line-height:1.6;white-space:pre-line;margin-bottom:12px">
              {{ \Illuminate\Support\Str::limit($row->text, 400) }}
            </p>
          @else
            <p style="font-size:12px;color:var(--p-hint);margin-bottom:12px">Matn yo‘q (faqat rasm/vote bo‘lishi mumkin).</p>
          @endif
          <form method="POST" action="{{ route('admin.book-club.post-ugc-score', $row) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="text-xs" style="color:var(--p-hint)">Bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:90px" required>
              @for($s = 1; $s <= 5; $s++)
                <option value="{{ $s }}">{{ $s }} ★</option>
              @endfor
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
            <a href="{{ route('admin.book-club.show', $row) }}" class="btn-p ghost sm">Post sahifasi</a>
          </form>
        </div>
      @empty
        <div style="text-align:center;padding:28px;color:var(--p-hint)">
          Kutilayotgan post yo‘q
        </div>
      @endforelse
    </div>
  </div>

  <div class="a122-section fade-up">
    <div class="a122-section-head">
      <div>
        <div class="a122-section-head__title">Asosiy izohlar (kutilmoqda)</div>
        <div class="a122-section-head__meta">{{ $pendingComments->count() }} ta izoh admin bahosini kutmoqda.</div>
      </div>
    </div>
    <div class="a122-section-body p-0">
      @forelse($pendingComments as $c)
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
          <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
            <div>
              @if($c->post)
                <a href="{{ route('admin.book-club.show', $c->post) }}" style="font-size:13px;font-weight:600;color:var(--p-accent);text-decoration:none">
                  Post #{{ $c->post_id }}
                </a>
              @else
                <span style="font-size:13px;font-weight:600">Post #{{ $c->post_id }}</span>
              @endif
              <span style="font-size:12px;color:var(--p-hint);margin-left:8px">
                {{ $c->created_at?->format('d.m.Y H:i') }}
              </span>
            </div>
            <span class="btn-p ghost sm" style="pointer-events:none;border-color:var(--p-warning);color:var(--p-warning)">
              <i class="bi bi-hourglass-split"></i> pending_admin
            </span>
          </div>
          @if($c->user)
            <div style="font-size:12px;color:var(--p-muted);margin-bottom:6px">
              {{ $c->user->name }} {{ $c->user->lastname }}
            </div>
          @endif
          <p style="font-size:13px;color:var(--p-text);line-height:1.6;white-space:pre-line;margin-bottom:12px">
            {{ \Illuminate\Support\Str::limit($c->content, 500) }}
          </p>
          @if($c->kangaroo_toxicity !== null)
            <div style="font-size:11px;color:var(--p-hint);margin-bottom:8px">
              Toxicity (Kangaroo): {{ number_format((float) $c->kangaroo_toxicity, 3) }}
            </div>
          @endif
          <form method="POST" action="{{ route('admin.book-club.comment.ugc-score', $c) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="text-xs" style="color:var(--p-hint)">Bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:90px" required>
              @for($s = 1; $s <= 5; $s++)
                <option value="{{ $s }}">{{ $s }} ★</option>
              @endfor
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
            @if($c->post)
              <a href="{{ route('admin.book-club.show', $c->post) }}" class="btn-p ghost sm">Post sahifasi</a>
            @endif
          </form>
        </div>
      @empty
        <div style="text-align:center;padding:28px;color:var(--p-hint)">
          Kutilayotgan izoh yo‘q
        </div>
      @endforelse
    </div>
  </div>

</div>

@endsection
