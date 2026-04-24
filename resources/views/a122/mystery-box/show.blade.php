@extends('a122.layouts.admin')
@section('title', 'Obuna #'.$subscription->id)
@section('page-title', 'Mystery Box obuna')

@section('content')

@php $addr = is_array($subscription->address) ? $subscription->address : []; @endphp

<x-a122.page-header back-href="{{ route('admin.mystery-box.index') }}">
  <x-slot name="heading">Obuna #{{ $subscription->id }}</x-slot>
  <x-slot name="actions">
    <div class="flex gap-2">
        @if($subscription->status === 'active')
        <form method="POST" action="{{ route('admin.mystery-box.pause', $subscription) }}">
          @csrf @method('PATCH')
          <button class="btn-p ghost"><i class="bi bi-pause-fill"></i> To'xtatish</button>
        </form>
        @elseif($subscription->status === 'paused')
        <form method="POST" action="{{ route('admin.mystery-box.resume', $subscription) }}">
          @csrf @method('PATCH')
          <button class="btn-p success"><i class="bi bi-play-fill"></i> Davom ettirish</button>
        </form>
        @endif
    
        @if(!in_array($subscription->status, ['cancelled','completed']))
        <form method="POST" action="{{ route('admin.mystery-box.cancel', $subscription) }}"
              onsubmit="return confirm('Obuna bekor qilinsinmi?')">
          @csrf @method('PATCH')
          <button class="btn-p danger ghost"><i class="bi bi-x-lg"></i> Bekor qilish</button>
        </form>
        @endif
      </div>
  </x-slot>
</x-a122.page-header>


<div class="grid grid-cols-1 xl:grid-cols-12 gap-3">

  {{-- ── Chap ─────────────────────────────────────────────── --}}
  <div class="xl:col-span-4">

    {{-- Foydalanuvchi --}}
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Foydalanuvchi</div></div>
      <div style="padding:14px 18px">
        @if($subscription->user)
        <div class="flex items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;
                      background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
                      display:flex;align-items:center;justify-content:center;
                      font-size:16px;font-weight:700;color:#fff">
            @if($subscription->user->avatar)
              <img src="{{ asset('storage/'.$subscription->user->avatar) }}"
                   style="width:100%;height:100%;object-fit:cover">
            @else
              {{ strtoupper(substr($subscription->user->name,0,1)) }}
            @endif
          </div>
          <div>
            <div style="font-size:14px;font-weight:600;color:var(--p-text)">
              {{ $subscription->user->name }} {{ $subscription->user->lastname }}
            </div>
            <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
              {{ $subscription->user->phone_number }}
            </div>
          </div>
        </div>
        <a href="{{ route('admin.users.show',$subscription->user_id) }}"
           class="btn-p ghost" style="width:100%;justify-content:center;font-size:12px">
          <i class="bi bi-person"></i> Profil
        </a>
        @endif
      </div>
    </div>

    {{-- Yetkazish manzili --}}
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-geo-alt mr-1" style="color:var(--p-accent)"></i> Yetkazish manzili
        </div>
      </div>
      <div style="padding:14px 18px">
        @foreach([
          ['Qabul qiluvchi', $addr['fullName'] ?? '—'],
          ['Telefon',        $addr['phoneNumber'] ?? '—'],
          ['Manzil',         $addr['fullAddress'] ?? '—'],
        ] as [$k,$v])
        <div style="margin-bottom:12px">
          <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                      letter-spacing:.07em;margin-bottom:3px">{{ $k }}</div>
          <div style="font-size:13px;color:var(--p-text);font-weight:500">{{ $v }}</div>
        </div>
        @endforeach

        @if(($addr['lat'] ?? null) && ($addr['lon'] ?? null))
        <a href="https://maps.yandex.uz/?text={{ $addr['lat'] }}+{{ $addr['lon'] }}&z=16"
           target="_blank" class="btn-p ghost sm">
          <i class="bi bi-map"></i> Xaritada ko'rish
        </a>
        @endif
      </div>
    </div>

    {{-- Tarif info --}}
    <div class="p-card fade-up">
      <div class="p-card-header"><div class="p-card-title">Tarif</div></div>
      <div style="padding:0 18px 14px">
        @foreach([
          ['Tarif',       $subscription->plan?->name_uz ?? '—'],
          ['Muddat',      $subscription->total_months.' oy'],
          ['Har oyda',    $subscription->books_per_month.' ta kitob'],
          ['To\'lov',     number_format($subscription->price_uzs).' UZS'],
          ['Boshlandi',   $subscription->started_at?->format('d.m.Y') ?? '—'],
          ['Tugaydi',     $subscription->ends_at?->format('d.m.Y') ?? '—'],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach

        {{-- Progress --}}
        <div style="margin-top:14px">
          <div class="flex justify-between mb-1">
            <span style="font-size:11px;color:var(--p-hint)">Bajarildi</span>
            <span style="font-size:11px;color:var(--p-muted);font-family:'JetBrains Mono',monospace">
              {{ $subscription->delivered_months }}/{{ $subscription->total_months }}
            </span>
          </div>
          <div style="height:8px;background:var(--p-elevated);border-radius:4px;overflow:hidden">
            <div style="height:100%;background:var(--p-success);border-radius:4px;
                        width:{{ $subscription->progress_pct }}%;transition:width .3s">
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ── O'ng: Yetkazishlar ───────────────────────────────── --}}
  <div class="xl:col-span-8">
    <div class="p-card fade-up">
      <div class="p-card-header">
        <div class="p-card-title">
          <i class="bi bi-box-seam mr-1" style="color:var(--p-accent)"></i>
          Oylik yetkazishlar
        </div>
        <span class="s-pill accent" style="font-size:10px">
          {{ $subscription->deliveries->count() }} ta
        </span>
      </div>

      @forelse($subscription->deliveries->sortBy('month_number') as $delivery)
      @php
        $books = $delivery->book_ids
          ? \App\Models\Books::whereIn('id', $delivery->book_ids)
              ->select('id','name','author','images')->get()
          : collect();
      @endphp
      <div style="padding:16px 18px;border-top:1px solid var(--p-border)">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between mb-3">
          <div class="flex items-center gap-3 min-w-0">
            <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;
                        background:var(--p-elevated);
                        display:flex;align-items:center;justify-content:center;
                        font-family:'JetBrains Mono',monospace;font-size:13px;
                        font-weight:700;color:var(--p-accent)">
              {{ $delivery->month_number }}
            </div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--p-text)">
                {{ $delivery->month_number }}-oy
              </div>
              @if($delivery->shipped_at)
              <div style="font-size:11px;color:var(--p-hint)">
                Jo'natildi: {{ $delivery->shipped_at->format('d.m.Y') }}
              </div>
              @elseif($delivery->prepared_at)
              <div style="font-size:11px;color:var(--p-hint)">
                Tayyorlandi: {{ $delivery->prepared_at->format('d.m.Y') }}
              </div>
              @endif
            </div>
          </div>
          <span class="s-pill {{ $delivery->status_color }}" style="font-size:10px;width:max-content">
            {{ $delivery->status_label }}
          </span>
        </div>

        @if(in_array($delivery->status, ['pending', 'preparing', 'delivered']))
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;padding:10px 12px;background:var(--p-elevated);border:1px solid var(--p-border);border-radius:10px;flex-wrap:wrap">
          <div>
            <div style="font-size:12px;font-weight:700;color:var(--p-text)">
              {{ $delivery->month_number }}-oy kitoblari
            </div>
            <div style="font-size:11px;color:var(--p-hint)">
              {{ $delivery->status === 'delivered' ? "Yetkazilgan oy tarkibini tahrirlashingiz mumkin" : ($delivery->status === 'preparing' ? "Tanlovni yangilashingiz mumkin" : "Bu oy uchun kitoblarni tanlang") }}
            </div>
          </div>
          @if($delivery->status === 'delivered')
          <button type="button"
                  class="btn-p ghost sm"
                  onclick="toggleMysteryEditor('{{ $delivery->id }}')"
                  id="editorToggle{{ $delivery->id }}"
                  aria-expanded="false">
            <i class="bi bi-chevron-down" id="editorToggleIcon{{ $delivery->id }}"></i>
            Tarkibni tahrirlash
          </button>
          @else
          <span class="btn-p ghost sm" style="pointer-events:none">
            <i class="bi bi-pencil-square"></i>
            {{ $delivery->status === 'preparing' ? "Tahrirlash ochiq" : "Kitob tanlash" }}
          </span>
          @endif
        </div>
        @endif

        {{-- Kitoblar --}}
        @if($books->count())
        <div class="flex flex-wrap gap-2 mb-3">
          @foreach($books as $book)
          @php
            $imgs = is_array($book->images) ? $book->images : json_decode($book->images??'[]',true);
            $img  = $imgs[0] ?? null;
          @endphp
          <a href="{{ route('admin.books.show',$book->id) }}" target="_blank"
             style="display:flex;align-items:center;gap:8px;padding:6px 10px;
                    background:var(--p-elevated);border-radius:8px;
                    border:1px solid var(--p-border);text-decoration:none;
                    min-width:160px">
            <div style="width:28px;height:38px;border-radius:4px;overflow:hidden;
                        flex-shrink:0;background:var(--p-hover)">
              @if($img)
                <img src="{{ $img }}" style="width:100%;height:100%;object-fit:cover">
              @else
                <div style="width:100%;height:100%;display:flex;align-items:center;
                            justify-content:center">
                  <i class="bi bi-book" style="font-size:12px;color:var(--p-hint)"></i>
                </div>
              @endif
            </div>
            <div style="min-width:0">
              <div style="font-size:11.5px;font-weight:500;color:var(--p-text);
                          white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                          max-width:110px">{{ $book->name }}</div>
              <div style="font-size:10px;color:var(--p-hint)">{{ $book->author }}</div>
            </div>
          </a>
          @endforeach
        </div>
        @endif

        {{-- Amallar --}}
        @if(in_array($delivery->status, ['pending', 'preparing', 'delivered']))
        <div id="editorWrap{{ $delivery->id }}" style="{{ $delivery->status === 'delivered' ? 'display:none;' : '' }}">
        <form method="POST"
              action="{{ route('admin.mystery-box.prepare', $delivery) }}"
              id="prepForm{{ $delivery->id }}">
          @csrf @method('PATCH')
          <div style="margin-bottom:10px;font-size:11px;color:var(--p-hint)">
            Bu oy uchun {{ $subscription->books_per_month }} ta kitob tanlang. `Aktiv kitoblar` sahifasidan kerakli kitob IDlarini olib, shu yerga vergul bilan kiriting.
          </div>
          <div class="flex flex-wrap gap-2 mb-2">
            <a href="{{ route('admin.books.index', ['tab' => 'active']) }}" target="_blank" class="btn-p ghost sm">
              <i class="bi bi-book"></i> Aktiv kitoblar
            </a>
            <a href="{{ route('admin.books.create') }}" target="_blank" class="btn-p ghost sm">
              <i class="bi bi-plus-lg"></i> Yangi kitob
            </a>
          </div>
          <div class="row g-2 items-end">
            <div class="col">
              <label class="p-form-label">
                Kitob IDlari (vergul bilan ajrating)
              </label>
              <input type="text" name="book_ids_raw" class="p-form-control"
                     placeholder="123, 456, 789"
                     value="{{ implode(', ', $delivery->book_ids ?? []) }}"
                     oninput="parseBookIds(this,'{{ $delivery->id }}')">
              <div id="selectedBooksHint{{ $delivery->id }}" style="margin-top:6px;font-size:11px;color:var(--p-hint)">
                {{ count($delivery->book_ids ?? []) }} / {{ $subscription->books_per_month }} ta tanlandi
              </div>
              <div id="selectedBooks{{ $delivery->id }}" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px">
                @foreach($books as $book)
                <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-elevated);max-width:100%">
                  <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#{{ $book->id }}</span>
                  <span style="font-size:12px;color:var(--p-text);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $book->name }}{{ $book->author ? ' · '.$book->author : '' }}</span>
                  <button type="button" onclick="removeMysteryBook({{ $delivery->id }}, {{ $book->id }})" class="btn-p ghost sm" style="margin-left:auto">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                @endforeach
              </div>
              <input type="hidden" name="book_ids" id="bookIds{{ $delivery->id }}" value='@json(array_values($delivery->book_ids ?? []))'>
            </div>
            <div class="col-auto">
              <input type="text" name="tracking_note" class="p-form-control"
                     value="{{ $delivery->tracking_note }}"
                     placeholder="Izoh (ixtiyoriy)">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn-p primary">
                <i class="bi bi-check-lg"></i> {{ $delivery->status === 'delivered' ? 'Delivered oyni yangilash' : ($delivery->status === 'preparing' ? 'Tanlovni yangilash' : 'Kitoblarni tasdiqlash') }}
              </button>
            </div>
          </div>
        </form>
        </div>
        @if($delivery->status === 'preparing')
        <div class="flex flex-wrap gap-2 mt-2">
          <form method="POST" action="{{ route('admin.mystery-box.ship', $delivery) }}">
            @csrf @method('PATCH')
            <button class="btn-p primary">
              <i class="bi bi-truck"></i> Jo'natildi
            </button>
          </form>
        </div>
        @endif

        @elseif($delivery->status === 'shipped')
        <form method="POST" action="{{ route('admin.mystery-box.deliver', $delivery) }}">
          @csrf @method('PATCH')
          <button class="btn-p success">
            <i class="bi bi-check-circle"></i> Yetkazildi
          </button>
        </form>

        @elseif($delivery->status === 'delivered')
        <div style="font-size:12px;color:var(--p-success);display:flex;align-items:center;gap:6px">
          <i class="bi bi-check-circle-fill"></i>
          {{ $delivery->delivered_at?->format('d.m.Y') }} da yetkazildi
        </div>
        @endif

        @if($delivery->tracking_note)
        <div style="margin-top:8px;font-size:12px;color:var(--p-muted);
                    background:var(--p-elevated);padding:8px 12px;border-radius:6px">
          <i class="bi bi-info-circle mr-1"></i>{{ $delivery->tracking_note }}
        </div>
        @endif
      </div>
      @empty
      <div style="padding:36px;text-align:center;color:var(--p-hint)">
        <i class="bi bi-box" style="font-size:28px;display:block;margin-bottom:8px"></i>
        Hali yetkazishlar yo'q
      </div>
      @endforelse
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
function toggleMysteryEditor(deliveryId) {
  const wrap = document.getElementById('editorWrap' + deliveryId);
  const toggle = document.getElementById('editorToggle' + deliveryId);
  const icon = document.getElementById('editorToggleIcon' + deliveryId);
  if (!wrap || !toggle || !icon) return;

  const isHidden = wrap.style.display === 'none';
  wrap.style.display = isHidden ? 'block' : 'none';
  toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
  icon.className = isHidden ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
}

async function renderSelectedMysteryBooks(deliveryId, ids) {
  const target = document.getElementById('selectedBooks' + deliveryId);
  if (!target) return;

  if (!ids.length) {
    target.innerHTML = '';
    return;
  }

  target.innerHTML = '<span style="font-size:11px;color:var(--p-hint)">Tanlangan kitoblar yuklanmoqda...</span>';

  try {
    const url = `{{ route('admin.mystery-box.books.search') }}?ids=${ids.join(',')}`;
    const res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
    const json = await res.json();
    const books = Array.isArray(json.data) ? json.data : [];

    target.innerHTML = books.map(book => `
      <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--p-border);border-radius:10px;background:var(--p-elevated);max-width:100%">
        <span style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#${book.id}</span>
        <span style="font-size:12px;color:var(--p-text);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${book.name}${book.author ? ' · ' + book.author : ''}</span>
        <button type="button" onclick="removeMysteryBook(${deliveryId}, ${book.id})" class="btn-p ghost sm" style="margin-left:auto">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    `).join('');
  } catch (e) {
    target.innerHTML = '<span style="font-size:11px;color:var(--p-danger)">Tanlangan kitoblarni yuklab bo\'lmadi</span>';
  }
}

function removeMysteryBook(deliveryId, bookId) {
  const input = document.querySelector(`#prepForm${deliveryId} input[name="book_ids_raw"]`);
  if (!input) return;

  const ids = input.value.split(',')
    .map(s => parseInt(s.trim()))
    .filter(n => !isNaN(n) && n > 0 && n !== bookId);

  input.value = ids.join(', ');
  parseBookIds(input, deliveryId);
}

function parseBookIds(input, deliveryId) {
  const ids = input.value.split(',')
    .map(s => parseInt(s.trim()))
    .filter(n => !isNaN(n) && n > 0);
  document.getElementById('bookIds' + deliveryId).value = JSON.stringify(ids);
  const hint = document.getElementById('selectedBooksHint' + deliveryId);
  if (hint) {
    const expected = '{{ $subscription->books_per_month }}';
    hint.textContent = `${ids.length} / ${expected} ta tanlandi`;
  }
  renderSelectedMysteryBooks(deliveryId, ids);
}
</script>
@endpush
