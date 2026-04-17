@extends('panel.layouts.panel')
@section('title', 'Post #'.$bookClub->id)
@section('page-title', 'Post #'.$bookClub->id)

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 fade-up">
  <div class="d-flex align-items-center gap-3">
    <a href="{{ route('panel.book-club.index') }}" class="btn-p ghost icon">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Post #{{ $bookClub->id }}</h1>
      <p class="page-sub">
        {{ $bookClub->created_at?->format('d.m.Y H:i') }}
        @if($bookClub->repost)
          · <span style="color:var(--p-info)"><i class="bi bi-repeat"></i> Repost</span>
        @endif
      </p>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('panel.book-club.edit', $bookClub) }}" class="btn-p ghost">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
    <form method="POST" action="{{ route('panel.book-club.destroy', $bookClub) }}"
          onsubmit="return confirm('Post o\'chirilsinmi?')">
      @csrf @method('DELETE')
      <button class="btn-p danger ghost"><i class="bi bi-trash"></i> O'chirish</button>
    </form>
  </div>
</div>

<div class="row g-3">

  {{-- ════ POST ASOSIY ════════════════════════════════════════ --}}
  <div class="col-xl-8">

    {{-- Post kartasi --}}
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-body">

        {{-- Muallif --}}
        <div class="d-flex align-items-center gap-3 mb-3">
          <a href="{{ route('panel.users.show', $bookClub->user_id) }}"
             style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:block">
            @if($bookClub->user?->avatar)
              <img src="{{ $bookClub->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:18px;font-weight:700;color:#fff">
                {{ strtoupper(substr($bookClub->user?->name ?? 'U', 0, 1)) }}
              </div>
            @endif
          </a>
          <div>
            <a href="{{ route('panel.users.show', $bookClub->user_id) }}"
               style="font-size:15px;font-weight:600;color:var(--p-text);text-decoration:none">
              {{ $bookClub->user?->name }} {{ $bookClub->user?->lastname }}
            </a>
            <div style="font-size:12px;color:var(--p-hint)">
              {{ $bookClub->created_at?->format('d.m.Y H:i') }}
            </div>
          </div>

          @if($bookClub->repost && $bookClub->originalAuthor)
          <div style="margin-left:auto;font-size:12px;color:var(--p-info);display:flex;align-items:center;gap:6px">
            <i class="bi bi-repeat"></i>
            <span>Repost:
              <a href="{{ route('panel.users.show', $bookClub->reposted_user_id) }}"
                 style="color:var(--p-info);font-weight:600">
                {{ $bookClub->originalAuthor->name }}
              </a>
            </span>
          </div>
          @endif
        </div>

        {{-- Matn --}}
        @if($bookClub->text)
        <div style="font-size:14px;color:var(--p-text);line-height:1.8;white-space:pre-line;margin-bottom:16px">
          {{ $bookClub->text }}
        </div>
        @endif

        {{-- Rasmlar --}}
        @if($bookClub->images->count())
        <div class="d-flex flex-wrap gap-2 mb-4">
          @foreach($bookClub->images as $img)
          <div style="position:relative">
            <a href="{{ asset('storage/'.$img->image) }}" target="_blank"
               style="display:block;width:100px;height:100px;border-radius:8px;
                      overflow:hidden;border:1px solid var(--p-border)">
              <img src="{{ asset('storage/'.$img->image) }}"
                   style="width:100%;height:100%;object-fit:cover">
            </a>
            <form method="POST"
                  action="{{ route('panel.book-club.image.delete', $img) }}"
                  style="position:absolute;top:4px;right:4px"
                  onsubmit="return confirm('Rasm o\'chirilsinmi?')">
              @csrf @method('DELETE')
              <button style="width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.6);
                             border:none;color:#fff;font-size:10px;cursor:pointer;
                             display:flex;align-items:center;justify-content:center">
                <i class="bi bi-x"></i>
              </button>
            </form>
          </div>
          @endforeach
        </div>
        @endif

        {{-- Mahsulot --}}
        @if($bookClub->product_id)
        <div style="background:var(--p-elevated);border-radius:10px;padding:12px 14px;margin-bottom:12px">
          <div style="font-size:11px;color:var(--p-hint);margin-bottom:6px;text-transform:uppercase;letter-spacing:.07em">
            Bog'liq mahsulot
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <i class="bi bi-{{ $bookClub->product_type === 'book' ? 'book' : 'pencil-square' }}"
               style="color:var(--p-accent);font-size:16px"></i>
            <span style="font-size:13px;font-weight:500;color:var(--p-text)">
              {{ ucfirst($bookClub->product_type) }} ID: #{{ $bookClub->product_id }}
            </span>
          </div>
        </div>
        @endif

        {{-- So'rovnoma --}}
        @if($bookClub->votes->count())
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;margin-bottom:12px">
          <div style="font-size:12px;color:var(--p-hint);margin-bottom:10px;
                      font-weight:600;text-transform:uppercase;letter-spacing:.07em">
            <i class="bi bi-bar-chart me-1"></i> So'rovnoma · {{ $totalVotes }} ta ovoz
          </div>
          @foreach($bookClub->votes as $vote)
          @php
            $vCount = \DB::table('book_club_voted_users')->where('option_id', $vote->id)->count();
            $pct = $totalVotes > 0 ? round($vCount / $totalVotes * 100) : 0;
          @endphp
          <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="font-size:13px;color:var(--p-text)">{{ $vote->option_text }}</span>
              <span style="font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--p-accent)">
                {{ $vCount }} ({{ $pct }}%)
              </span>
            </div>
            <div class="dash-prog-track">
              <div class="dash-prog-fill"
                   style="width:{{ $pct }}%;background:var(--p-accent)"></div>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        {{-- Stats --}}
        <div style="display:flex;gap:20px;padding-top:12px;border-top:1px solid var(--p-border)">
          @foreach([
            ['bi-heart-fill','danger', $likesCount, 'like'],
            ['bi-chat-fill', 'accent', $comments->total(), 'izoh'],
            ['bi-repeat',    'info',   $repostsCount, 'repost'],
          ] as [$icon,$clr,$cnt,$lbl])
          <div style="display:flex;align-items:center;gap:5px">
            <i class="bi {{ $icon }}" style="color:var(--p-{{ $clr }})"></i>
            <span style="font-size:13px;font-weight:600;font-family:'JetBrains Mono',monospace;color:var(--p-text)">
              {{ $cnt }}
            </span>
            <span style="font-size:11px;color:var(--p-hint)">{{ $lbl }}</span>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- ── Izohlar ─────────────────────────────────────────── --}}
    <div class="p-card fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">Izohlar</div>
        <div class="dash-card-sub">{{ $comments->total() }} ta</div>
      </div>
      <div class="dash-card-body p-0">

        @forelse($comments as $comment)
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">

          {{-- Izoh --}}
          <div class="d-flex gap-3">
            <a href="{{ route('panel.users.show', $comment->user_id) }}"
               style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);display:block">
              @if($comment->user?->avatar)
                <img src="{{ $comment->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
              @else
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                            font-size:12px;font-weight:700;color:#fff">
                  {{ strtoupper(substr($comment->user?->name ?? 'U', 0, 1)) }}
                </div>
              @endif
            </a>
            <div style="flex:1;min-width:0">
              <div class="d-flex align-items-start justify-content-between gap-2">
                <div>
                  <a href="{{ route('panel.users.show', $comment->user_id) }}"
                     style="font-size:13px;font-weight:600;color:var(--p-text);text-decoration:none">
                    {{ $comment->user?->name }} {{ $comment->user?->lastname }}
                  </a>
                  <span style="font-size:11px;color:var(--p-hint);margin-left:8px">
                    {{ $comment->created_at?->format('d.m.Y H:i') }}
                  </span>
                </div>
                <div class="d-flex gap-1">
                  {{-- Tahrirlash modal trigger --}}
                  <button class="btn-p ghost sm" title="Tahrirlash"
                          onclick="editComment({{ $comment->id }}, `{{ addslashes($comment->content) }}`)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST"
                        action="{{ route('panel.book-club.comment.delete', $comment) }}"
                        onsubmit="return confirm('Izoh o\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </div>
              <p style="font-size:13px;color:var(--p-muted);margin:6px 0 8px;
                        line-height:1.6;white-space:pre-line">{{ $comment->content }}</p>
              <div style="font-size:11px;color:var(--p-hint);display:flex;gap:12px">
                <span>
                  <i class="bi bi-heart-fill" style="color:var(--p-danger);font-size:10px"></i>
                  {{ $comment->likes_count ?? $comment->likes->count() }}
                </span>
                @if($comment->replies_count > 0)
                <span style="color:var(--p-accent)">
                  <i class="bi bi-chat"></i> {{ $comment->replies_count }} ta javob
                </span>
                @endif
              </div>

              {{-- Javoblar --}}
              @if($comment->replies->count())
              <div style="margin-top:12px;padding-left:16px;border-left:2px solid var(--p-border)">
                @foreach($comment->replies->take(3) as $reply)
                <div style="display:flex;gap:10px;margin-bottom:10px">
                  <a href="{{ route('panel.users.show', $reply->user_id) }}"
                     style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
                            background:linear-gradient(135deg,#7c5cfc,var(--p-accent));display:block">
                    @if($reply->user?->avatar)
                      <img src="{{ $reply->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                      <div style="width:100%;height:100%;display:flex;align-items:center;
                                  justify-content:center;font-size:11px;font-weight:700;color:#fff">
                        {{ strtoupper(substr($reply->user?->name ?? 'U', 0, 1)) }}
                      </div>
                    @endif
                  </a>
                  <div style="flex:1">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <a href="{{ route('panel.users.show', $reply->user_id) }}"
                           style="font-size:12px;font-weight:600;color:var(--p-text);text-decoration:none">
                          {{ $reply->user?->name }}
                        </a>
                        <span style="font-size:10px;color:var(--p-hint);margin-left:6px">
                          {{ $reply->created_at?->format('d.m.Y H:i') }}
                        </span>
                      </div>
                      <form method="POST"
                            action="{{ route('panel.book-club.comment.delete', $reply) }}"
                            onsubmit="return confirm('Javob o\'chirilsinmi?')">
                        @csrf @method('DELETE')
                        <button class="btn-p danger sm" style="padding:2px 6px">
                          <i class="bi bi-trash" style="font-size:10px"></i>
                        </button>
                      </form>
                    </div>
                    <p style="font-size:12px;color:var(--p-muted);margin:4px 0;line-height:1.5">
                      {{ $reply->content }}
                    </p>
                    <div style="font-size:10px;color:var(--p-hint)">
                      <i class="bi bi-heart-fill" style="color:var(--p-danger);font-size:9px"></i>
                      {{ $reply->likes->count() }}
                    </div>
                  </div>
                </div>
                @endforeach

                @if($comment->replies_count > 3)
                <div style="font-size:11px;color:var(--p-hint);padding-left:8px">
                  va yana {{ $comment->replies_count - 3 }} ta javob...
                </div>
                @endif
              </div>
              @endif
            </div>
          </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--p-hint)">
          <i class="bi bi-chat-square" style="font-size:28px;display:block;margin-bottom:8px"></i>
          Izohlar yo'q
        </div>
        @endforelse

      </div>
      {{ $comments->links('panel.partials.pagination') }}
    </div>

  </div>

  {{-- ════ O'NG: Like, Repost, Meta ═════════════════════════ --}}
  <div class="col-xl-4">

    {{-- Meta --}}
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlar</div></div>
      <div class="dash-card-body">
        @foreach([
          ['Post ID',      '#'.$bookClub->id],
          ['Tur',          $bookClub->repost ? 'Repost' : 'Original post'],
          ['Yaratildi',    $bookClub->created_at?->format('d.m.Y H:i')],
          ['Mahsulot tur', $bookClub->product_type ? ucfirst($bookClub->product_type) : '—'],
          ['Mahsulot ID',  $bookClub->product_id ? '#'.$bookClub->product_id : '—'],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Like bosganlar --}}
    <div class="p-card mb-3 fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">
          <i class="bi bi-heart-fill me-1" style="color:var(--p-danger)"></i> Like bosganlar
        </div>
        <span class="s-pill danger">{{ $likesCount }}</span>
      </div>
      <div class="dash-card-body">
        @forelse($likers as $like)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="{{ route('panel.users.show', $like->user_id) }}"
             style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-danger),#ff8fab);display:block">
            @if($like->user?->avatar)
              <img src="{{ $like->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                {{ strtoupper(substr($like->user?->name ?? 'U', 0, 1)) }}
              </div>
            @endif
          </a>
          <div style="flex:1;min-width:0">
            <a href="{{ route('panel.users.show', $like->user_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">
              {{ $like->user?->name }} {{ $like->user?->lastname }}
            </a>
            <span style="font-size:10px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              #{{ $like->user_id }}
            </span>
          </div>
          <span style="font-size:10px;color:var(--p-hint);white-space:nowrap">
            {{ $like->created_at?->format('d.m') }}
          </span>
        </div>
        @empty
        <div style="text-align:center;padding:20px;color:var(--p-hint);font-size:13px">
          Like yo'q
        </div>
        @endforelse
        @if($likesCount > 20)
          <div style="font-size:11px;color:var(--p-hint);padding-top:8px;text-align:center">
            va yana {{ $likesCount - 20 }} ta...
          </div>
        @endif
      </div>
    </div>

    {{-- Repost qilganlar --}}
    <div class="p-card fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">
          <i class="bi bi-repeat me-1" style="color:var(--p-info)"></i> Repost qilganlar
        </div>
        <span class="s-pill info">{{ $repostsCount }}</span>
      </div>
      <div class="dash-card-body">
        @forelse($reposters as $rp)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="{{ route('panel.users.show', $rp->user_id) }}"
             style="width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;
                    background:linear-gradient(135deg,var(--p-info),#0ea5e9);display:block">
            @if($rp->user?->avatar)
              <img src="{{ $rp->user->avatar }}" style="width:100%;height:100%;object-fit:cover">
            @else
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                          font-size:11px;font-weight:700;color:#fff">
                {{ strtoupper(substr($rp->user?->name ?? 'U', 0, 1)) }}
              </div>
            @endif
          </a>
          <div style="flex:1;min-width:0">
            <a href="{{ route('panel.users.show', $rp->user_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">
              {{ $rp->user?->name }} {{ $rp->user?->lastname }}
            </a>
          </div>
          <a href="{{ route('panel.book-club.show', $rp) }}" class="btn-p ghost sm">
            <i class="bi bi-eye"></i>
          </a>
        </div>
        @empty
        <div style="text-align:center;padding:20px;color:var(--p-hint);font-size:13px">
          Repostlar yo'q
        </div>
        @endforelse
      </div>
    </div>

  </div>
</div>

{{-- ── Izoh tahrirlash modal ──────────────────────────────── --}}
<div id="editCommentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
     z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--p-surface);border-radius:14px;padding:24px;width:100%;max-width:480px;
              border:1px solid var(--p-border)">
    <div style="font-size:16px;font-weight:600;color:var(--p-text);margin-bottom:16px">
      <i class="bi bi-pencil me-1"></i> Izohni tahrirlash
    </div>
    <form method="POST" id="editCommentForm">
      @csrf @method('PUT')
      <textarea name="content" id="editCommentContent" rows="4"
                class="p-form-control" style="width:100%;margin-bottom:12px"
                required></textarea>
      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn-p ghost"
                onclick="closeEditModal()">Bekor qilish</button>
        <button type="submit" class="btn-p">
          <i class="bi bi-check-lg"></i> Saqlash
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
function editComment(id, content) {
  const modal = document.getElementById('editCommentModal');
  document.getElementById('editCommentContent').value = content;
  document.getElementById('editCommentForm').action =
    `/panel/book-club/comments/${id}`;
  modal.style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editCommentModal').style.display = 'none';
}

document.getElementById('editCommentModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});
</script>
@endpush