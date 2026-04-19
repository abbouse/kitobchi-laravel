{{-- panel/book-club/_post-card.blade.php --}}
{{-- $post, $showUser (default: true) --}}
@php $showUser = $showUser ?? true; @endphp

<div class="p-card fade-up" style="height:100%">
  <div class="dash-card-body">

    {{-- User va meta --}}
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-3">
        @if($showUser && $post->user)
        <a href="{{ route('panel.users.show', $post->user_id) }}"
           style="display:block;width:38px;height:38px;border-radius:50%;overflow:hidden;
                  background:linear-gradient(135deg,var(--p-accent),#7c5cfc);flex-shrink:0">
          @if($post->user->avatar)
            <img src="{{ $post->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
          @else
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;color:#fff">
              {{ strtoupper(substr($post->user->name ?? 'U', 0, 1)) }}
            </div>
          @endif
        </a>
        <div>
          <a href="{{ route('panel.users.show', $post->user_id) }}"
             style="font-size:13px;font-weight:600;color:var(--p-text);text-decoration:none">
            {{ $post->user->name }} {{ $post->user->lastname }}
          </a>
          <div style="font-size:11px;color:var(--p-hint)">
            {{ $post->created_at?->diffForHumans() }}
            @if($post->is_repost ?? $post->repost)
              · <span style="color:var(--p-info)"><i class="bi bi-repeat"></i> Repost</span>
            @endif
          </div>
        </div>
        @else
        <div style="font-size:12px;color:var(--p-hint)">
          {{ $post->created_at?->diffForHumans() }}
        </div>
        @endif
      </div>

      {{-- Amallar --}}
      <div class="flex gap-1">
        <a href="{{ route('panel.book-club.show', $post) }}" class="btn-p ghost sm">
          <i class="bi bi-eye"></i>
        </a>
        <a href="{{ route('panel.book-club.edit', $post) }}" class="btn-p ghost sm">
          <i class="bi bi-pencil"></i>
        </a>
        <form method="POST" action="{{ route('panel.book-club.destroy', $post) }}"
              onsubmit="return confirm('Post o\'chirilsinmi?')">
          @csrf @method('DELETE')
          <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </div>

    {{-- Matn --}}
    @if($post->text)
    <p style="font-size:14px;color:var(--p-text);line-height:1.7;margin-bottom:12px;
              white-space:pre-line">{{ Str::limit($post->text, 200) }}</p>
    @endif

    {{-- Rasmlar --}}
    @if($post->images && $post->images->count())
    <div class="flex flex-wrap gap-2 mb-12" style="margin-bottom:12px">
      @foreach($post->images->take(4) as $img)
      <a href="{{ asset('storage/'.$img->image) }}" target="_blank"
         style="width:80px;height:80px;border-radius:8px;overflow:hidden;display:block;
                background:var(--p-elevated);flex-shrink:0">
        <img src="{{ asset('storage/'.$img->image) }}"
             style="width:100%;height:100%;object-fit:cover">
      </a>
      @endforeach
      @if($post->images->count() > 4)
        <div style="width:80px;height:80px;border-radius:8px;background:var(--p-elevated);
                    display:flex;align-items:center;justify-content:center;font-size:13px;
                    color:var(--p-hint)">+{{ $post->images->count() - 4 }}</div>
      @endif
    </div>
    @endif

    {{-- Mahsulot --}}
    @if($post->product_id)
    <div style="display:inline-flex;align-items:center;gap:6px;background:var(--p-elevated);
                border-radius:8px;padding:6px 10px;font-size:12px;color:var(--p-muted);margin-bottom:10px">
      <i class="bi bi-{{ $post->product_type === 'book' ? 'book' : 'pencil-square' }}"
         style="color:var(--p-accent)"></i>
      {{ $post->product_type === 'book' ? 'Kitob' : 'Kanstovar' }} #{{ $post->product_id }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="flex items-center gap-3 mt-2"
         style="padding-top:10px;border-top:1px solid var(--p-border)">
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-heart" style="color:var(--p-danger)"></i>
        {{ number_format($post->likes_count ?? 0) }}
      </span>
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-chat" style="color:var(--p-accent)"></i>
        {{ number_format($post->comments_count ?? 0) }}
      </span>
      @if($post->votes && $post->votes->count())
      <span style="font-size:12px;color:var(--p-muted);display:flex;align-items:center;gap:4px">
        <i class="bi bi-bar-chart" style="color:var(--p-info)"></i>
        So'rovnoma · {{ $post->votes->count() }} variant
      </span>
      @endif
      <span style="font-size:11px;color:var(--p-hint);margin-left:auto">
        #{{ $post->id }}
      </span>
    </div>

  </div>
</div>