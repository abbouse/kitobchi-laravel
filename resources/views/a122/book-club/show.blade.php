@extends('a122.layouts.admin')
@section('title', 'Post #'.$bookClub->id)
@section('page-title', 'Post #'.$bookClub->id)

@section('content')

<x-a122.page-header back-href="{{ route('admin.book-club.index') }}">
  <x-slot name="heading">Post #{{ $bookClub->id }}</x-slot>
  <x-slot name="meta">
    <p class="page-sub">
      {{ $bookClub->created_at?->format('d.m.Y H:i') }}
      @if($bookClub->repost)
        · <span style="color:var(--p-info)"><i class="bi bi-repeat"></i> Repost</span>
      @endif
    </p>
  </x-slot>
  <x-slot name="actions">
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('admin.book-club.moderation-queue') }}" class="btn-p ghost">
        <i class="bi bi-shield-exclamation"></i> UGC navbati
      </a>
      <a href="{{ route('admin.book-club.edit', $bookClub) }}" class="btn-p ghost">
        <i class="bi bi-pencil"></i> Tahrirlash
      </a>
      <form method="POST" action="{{ route('admin.book-club.destroy', $bookClub) }}"
            onsubmit="return confirm('Post o\'chirilsinmi?')">
        @csrf @method('DELETE')
        <button class="btn-p danger ghost"><i class="bi bi-trash"></i> O'chirish</button>
      </form>
    </div>
  </x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  {{-- ════ POST ASOSIY ════════════════════════════════════════ --}}
  <div class="xl:col-span-8">
    <div class="a122-section mb-3 fade-up">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Moderatsiya va ogohlantirish</div>
          <div class="a122-section-head__meta">{{ $activeWarningCount }} ta faol ogohlantirish mavjud.</div>
        </div>
      </div>
      <div class="a122-section-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px">
          <span class="btn-p ghost sm" style="pointer-events:none">
            <i class="bi bi-person"></i> User #{{ $bookClub->user_id }}
          </span>
          <span class="btn-p ghost sm" style="pointer-events:none;border-color:{{ $bookClub->activeWarning ? 'var(--p-warning)' : 'var(--p-border)' }};color:{{ $bookClub->activeWarning ? 'var(--p-warning)' : 'var(--p-hint)' }}">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $bookClub->activeWarning ? "Post ogohlantirilgan" : "Ogohlantirish yo'q" }}
          </span>
        </div>

        @if($bookClub->activeWarning)
          <div style="padding:12px 14px;border-radius:12px;background:var(--p-warning-d);border:1px solid rgba(245,166,35,.18);margin-bottom:14px">
            <div style="font-size:12px;color:var(--p-warning);font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">
              So‘nggi ogohlantirish
            </div>
            <div style="font-size:13px;color:var(--p-text);line-height:1.7;white-space:pre-line">{{ $bookClub->activeWarning->note }}</div>
            <div style="font-size:11px;color:var(--p-hint);margin-top:8px">
              {{ $bookClub->activeWarning->created_at?->format('d.m.Y H:i') }}
              @if($bookClub->activeWarning->admin)
                · {{ $bookClub->activeWarning->admin->name }}
              @endif
            </div>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.book-club.warn', $bookClub) }}">
          @csrf
          <label style="display:block;font-size:12px;color:var(--p-hint);margin-bottom:6px">Admin izohi</label>
          <textarea name="note" rows="4" class="p-form-control" placeholder="Nega ogohlantirish berilayotganini yozing..." required>{{ old('note', $bookClub->activeWarning?->note) }}</textarea>
          @error('note')
            <div style="font-size:12px;color:var(--p-danger);margin-top:6px">{{ $message }}</div>
          @enderror
          <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn-p warning">
              <i class="bi bi-exclamation-triangle"></i>
              {{ $bookClub->activeWarning ? "Ogohlantirishni yangilash" : "Ogohlantirish berish" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Post kartasi --}}
    <div class="a122-section mb-3 fade-up">
      <div class="a122-section-body">

        {{-- Muallif --}}
        <div class="flex items-center gap-3 mb-3">
          <a href="{{ route('admin.users.show', $bookClub->user_id) }}"
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
            <a href="{{ route('admin.users.show', $bookClub->user_id) }}"
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
              <a href="{{ route('admin.users.show', $bookClub->reposted_user_id) }}"
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
        <div class="flex flex-wrap gap-2 mb-4">
          @foreach($bookClub->images as $img)
          <div style="position:relative">
            <a href="{{ asset('storage/'.$img->image) }}" target="_blank"
               style="display:block;width:100px;height:100px;border-radius:8px;
                      overflow:hidden;border:1px solid var(--p-border)">
              <img src="{{ asset('storage/'.$img->image) }}"
                   style="width:100%;height:100%;object-fit:cover">
            </a>
            <form method="POST"
                  action="{{ route('admin.book-club.image.delete', $img) }}"
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
            <i class="bi bi-bar-chart mr-1"></i> So'rovnoma · {{ $totalVotes }} ta ovoz
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

        {{-- Kangaroo: post matni UGC (bitta blok) --}}
        @if($bookClub->kangaroo_post_ugc_status || $bookClub->kangaroo_post_star !== null || $bookClub->kangaroo_post_checked_at)
        <div style="background:var(--p-elevated);border-radius:10px;padding:14px;margin-bottom:12px;border:1px solid var(--p-border)">
          <div style="font-size:12px;font-weight:600;color:var(--p-hint);margin-bottom:10px;text-transform:uppercase;letter-spacing:.07em">
            <i class="bi bi-stars mr-1"></i> Kangaroo · post matni
          </div>
          <div style="font-size:13px;color:var(--p-text);display:flex;flex-wrap:wrap;gap:12px;margin-bottom:10px">
            @if($bookClub->kangaroo_post_ugc_status)
              <span>Holat:
                @if($bookClub->kangaroo_post_ugc_status === 'pending_admin')
                  <strong style="color:var(--p-warning)">admin navbati</strong>
                @elseif($bookClub->kangaroo_post_ugc_status === 'admin_scored')
                  <strong style="color:var(--p-accent)">admin bahosi</strong>
                @else
                  <strong>{{ $bookClub->kangaroo_post_ugc_status }}</strong>
                @endif
              </span>
            @endif
            @if($bookClub->kangaroo_post_star !== null)
              <span>Matnga nisbatan baho: <strong>{{ number_format((float) $bookClub->kangaroo_post_star, 2) }}</strong> / 5</span>
            @endif
            @if($bookClub->kangaroo_post_checked_at)
              <span style="color:var(--p-hint)">Tekshirilgan: {{ $bookClub->kangaroo_post_checked_at->format('d.m.Y H:i') }}</span>
            @endif
          </div>
          @if($bookClub->kangaroo_post_ugc_status === 'pending_admin')
          <form method="POST" action="{{ route('admin.book-club.post-ugc-score', $bookClub) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label style="font-size:12px;color:var(--p-hint)">Admin bahosi (1–5)</label>
            <select name="star" class="p-form-control" style="width:88px" required>
              @for($s = 1; $s <= 5; $s++)
                <option value="{{ $s }}">{{ $s }} ★</option>
              @endfor
            </select>
            <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
          </form>
          @endif
        </div>
        @elseif($bookClub->text)
        <div style="background:var(--p-elevated);border-radius:10px;padding:12px 14px;margin-bottom:12px;font-size:12px;color:var(--p-hint)">
          <i class="bi bi-stars mr-1"></i> Kangaroo tekshiruvi hali yozilmagan (sinxron yoki cron: <code>kangaroo:sync-content-moderation</code>).
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
    <div class="a122-section fade-up">
      <div class="a122-section-head">
        <div>
          <div class="a122-section-head__title">Izohlar</div>
          <div class="a122-section-head__meta">{{ $comments->total() }} ta izoh va javoblar oqimi.</div>
        </div>
      </div>
      <div class="a122-section-body p-0">

        @forelse($comments as $comment)
        <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">

          {{-- Izoh --}}
          <div class="flex gap-3">
            <a href="{{ route('admin.users.show', $comment->user_id) }}"
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
              <div class="flex items-start justify-between gap-2">
                <div>
                  <a href="{{ route('admin.users.show', $comment->user_id) }}"
                     style="font-size:13px;font-weight:600;color:var(--p-text);text-decoration:none">
                    {{ $comment->user?->name }} {{ $comment->user?->lastname }}
                  </a>
                  <span style="font-size:11px;color:var(--p-hint);margin-left:8px">
                    {{ $comment->created_at?->format('d.m.Y H:i') }}
                  </span>
                </div>
                <div class="flex gap-1">
                  {{-- Tahrirlash modal trigger --}}
                  <button class="btn-p ghost sm" title="Tahrirlash"
                          onclick="editComment({{ $comment->id }}, `{{ addslashes($comment->content) }}`)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST"
                        action="{{ route('admin.book-club.comment.delete', $comment) }}"
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

              @if($comment->kangaroo_ugc_status || $comment->kangaroo_star_equivalent !== null || $comment->kangaroo_toxicity !== null || $comment->kangaroo_checked_at)
              <div style="margin-top:10px;padding:10px 12px;background:var(--p-elevated);border-radius:8px;border:1px solid var(--p-border)">
                <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Kangaroo · izoh</div>
                <div style="font-size:12px;color:var(--p-muted);display:flex;flex-wrap:wrap;gap:10px;margin-bottom:8px">
                  <span>Holat:
                    @if($comment->kangaroo_ugc_status === 'pending_admin')
                      <strong style="color:var(--p-warning)">admin navbati</strong>
                    @elseif($comment->kangaroo_ugc_status === 'admin_scored')
                      <strong style="color:var(--p-accent)">admin bahosi</strong>
                    @else
                      <strong style="color:var(--p-text)">{{ $comment->kangaroo_ugc_status ?? '—' }}</strong>
                    @endif
                  </span>
                  @if($comment->kangaroo_star_equivalent !== null)
                    <span>Izohga nisbatan baho: <strong style="color:var(--p-text)">{{ number_format((float) $comment->kangaroo_star_equivalent, 2) }}</strong> / 5</span>
                  @endif
                  @if($comment->kangaroo_toxicity !== null)
                    <span>Toxicity: <strong style="color:var(--p-text)">{{ number_format((float) $comment->kangaroo_toxicity, 3) }}</strong></span>
                  @endif
                </div>
                @if($comment->kangaroo_ugc_status === 'pending_admin')
                <form method="POST" action="{{ route('admin.book-club.comment.ugc-score', $comment) }}" class="flex flex-wrap items-end gap-2">
                  @csrf
                  <label style="font-size:11px;color:var(--p-hint)">Admin bahosi (1–5)</label>
                  <select name="star" class="p-form-control" style="width:88px" required>
                    @for($s = 1; $s <= 5; $s++)
                      <option value="{{ $s }}">{{ $s }} ★</option>
                    @endfor
                  </select>
                  <button type="submit" class="btn-p primary sm"><i class="bi bi-check2"></i> Saqlash</button>
                </form>
                @endif
              </div>
              @endif

              {{-- Javoblar --}}
              @if($comment->replies->count())
              <div style="margin-top:12px;padding-left:16px;border-left:2px solid var(--p-border)">
                @foreach($comment->replies->take(3) as $reply)
                <div style="display:flex;gap:10px;margin-bottom:10px">
                  <a href="{{ route('admin.users.show', $reply->user_id) }}"
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
                    <div class="flex items-center justify-between">
                      <div>
                        <a href="{{ route('admin.users.show', $reply->user_id) }}"
                           style="font-size:12px;font-weight:600;color:var(--p-text);text-decoration:none">
                          {{ $reply->user?->name }}
                        </a>
                        <span style="font-size:10px;color:var(--p-hint);margin-left:6px">
                          {{ $reply->created_at?->format('d.m.Y H:i') }}
                        </span>
                      </div>
                      <form method="POST"
                            action="{{ route('admin.book-club.comment.delete', $reply) }}"
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
      {{ $comments->links('a122.partials.pagination') }}
    </div>

  </div>

  {{-- ════ O'NG: Like, Repost, Meta ═════════════════════════ --}}
  <div class="xl:col-span-4">

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
          <i class="bi bi-heart-fill mr-1" style="color:var(--p-danger)"></i> Like bosganlar
        </div>
        <span class="s-pill danger">{{ $likesCount }}</span>
      </div>
      <div class="dash-card-body">
        @forelse($likers as $like)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="{{ route('admin.users.show', $like->user_id) }}"
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
            <a href="{{ route('admin.users.show', $like->user_id) }}"
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
          <i class="bi bi-repeat mr-1" style="color:var(--p-info)"></i> Repost qilganlar
        </div>
        <span class="s-pill info">{{ $repostsCount }}</span>
      </div>
      <div class="dash-card-body">
        @forelse($reposters as $rp)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                    border-bottom:1px solid var(--p-border)">
          <a href="{{ route('admin.users.show', $rp->user_id) }}"
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
            <a href="{{ route('admin.users.show', $rp->user_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text);text-decoration:none;
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">
              {{ $rp->user?->name }} {{ $rp->user?->lastname }}
            </a>
          </div>
          <a href="{{ route('admin.book-club.show', $rp) }}" class="btn-p ghost sm">
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
      <i class="bi bi-pencil mr-1"></i> Izohni tahrirlash
    </div>
    <form method="POST" id="editCommentForm">
      @csrf @method('PUT')
      <textarea name="content" id="editCommentContent" rows="4"
                class="p-form-control" style="width:100%;margin-bottom:12px"
                required></textarea>
      <div class="flex gap-2 justify-end">
        <button type="button" class="btn-p ghost"
                onclick="closeEditModal()">Bekor qilish</button>
        <button type="submit" class="btn-p primary">
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
