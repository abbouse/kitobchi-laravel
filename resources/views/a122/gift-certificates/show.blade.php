@extends('a122.layouts.admin')
@section('title', 'Sertifikat '.$giftCertificate->code)
@section('page-title', 'Gift Sertifikat')

@section('content')

<x-a122.page-header back-href="{{ route('admin.gift-certificates.index') }}">
  <x-slot name="heading">
    <h1 class="page-title font-mono text-lg font-semibold tracking-wide text-gray-800 sm:text-xl dark:text-white/90" style="letter-spacing:0.04em">
      {{ $giftCertificate->code }}
    </h1>
  </x-slot>
  <x-slot name="meta">
    <p class="page-sub">
      Yaratildi: {{ $giftCertificate->created_at?->format('d.m.Y H:i') }}
      @if($giftCertificate->expires_at && $giftCertificate->status === 'active')
        · <span style="color:{{ $giftCertificate->is_expired ? 'var(--p-danger)' : 'var(--p-warning)' }}">
          Muddati: {{ $giftCertificate->expires_at->format('d.m.Y') }}
          ({{ $giftCertificate->expires_at->diffForHumans() }})
        </span>
      @endif
    </p>
  </x-slot>
  <x-slot name="actions">
    <div class="flex flex-wrap gap-2">
      @if(!in_array($giftCertificate->status, ['used','cancelled']))
      <div class="dropdown">
        <button class="btn-p ghost" data-bs-toggle="dropdown">
          <i class="bi bi-chevron-down"></i> Status
        </button>
        <ul class="dropdown-menu dropdown-menu-end"
            style="background:var(--p-surface);border:1px solid var(--p-border);
                   border-radius:10px;min-width:180px;padding:6px">
          @foreach([
            'pending_payment' => 'To\'lov kutilmoqda',
            'paid'            => 'To\'landi',
            'active'          => 'Faollashtirildi',
            'used'            => 'Ishlatildi',
            'cancelled'       => 'Bekor qilindi',
          ] as $val => $lbl)
          <li>
            <form method="POST"
                  action="{{ route('admin.gift-certificates.status', $giftCertificate) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="status" value="{{ $val }}">
              <button type="submit" class="dropdown-item"
                      style="font-size:13px;padding:8px 12px;border-radius:6px;
                             background:{{ $val===$giftCertificate->status?'var(--p-elevated)':'transparent' }};
                             color:{{ $val==='cancelled'?'var(--p-danger)':($val===$giftCertificate->status?'var(--p-accent)':'var(--p-text)') }}">
                {{ $val===$giftCertificate->status ? '● ' : '○ ' }}{{ $lbl }}
              </button>
            </form>
          </li>
          @endforeach
        </ul>
      </div>

      <form method="POST"
            action="{{ route('admin.gift-certificates.cancel', $giftCertificate) }}"
            onsubmit="return confirm('Bekor qilinsinmi?')">
        @csrf
        <button class="btn-p danger ghost">
          <i class="bi bi-x-lg"></i> Bekor qilish
        </button>
      </form>
      @endif
    </div>
  </x-slot>
</x-a122.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

  {{-- ── Chap: Sertifikat kartasi ─────────────────────────── --}}
  <div class="xl:col-span-4">

    {{-- Visual karta --}}
    <div class="p-card mb-3 fade-up" style="overflow:hidden">
      <div style="padding:24px 22px;background:linear-gradient(135deg,
                  var(--p-accent-d) 0%,var(--p-elevated) 100%);
                  border-bottom:1px solid var(--p-border)">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    margin-bottom:20px">
          <div style="font-size:12px;font-weight:600;color:var(--p-muted);
                      text-transform:uppercase;letter-spacing:.1em">
            Gift Certificate
          </div>
          <i class="bi bi-gift" style="font-size:22px;color:var(--p-accent)"></i>
        </div>
        <div style="font-size:26px;font-weight:700;font-family:'JetBrains Mono',monospace;
                    color:var(--p-success);margin-bottom:4px">
          {{ number_format($giftCertificate->nominal_uzs) }}
          <span style="font-size:14px;font-weight:400">UZS</span>
        </div>
        <div style="font-size:11px;color:var(--p-muted)">Balans sifatida qo'shiladi</div>
      </div>
      <div style="padding:14px 22px">
        <div style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;
                    color:var(--p-text);letter-spacing:.1em;margin-bottom:8px">
          {{ $giftCertificate->code }}
        </div>
        <span class="s-pill {{ $giftCertificate->status_color }}" style="font-size:11px">
          {{ $giftCertificate->status_label }}
        </span>
      </div>
    </div>

    {{-- Tafsilotlar --}}
    <div class="p-card mb-3 fade-up">
      <div class="p-card-header"><div class="p-card-title">Tafsilotlar</div></div>
      <div style="padding:0 18px 14px">
        @foreach([
          ['Miqdor',       number_format($giftCertificate->nominal_uzs).' UZS'],
          ['Yaratildi',    $giftCertificate->created_at?->format('d.m.Y H:i')],
          ['To\'landi',    $giftCertificate->paid_at?->format('d.m.Y H:i') ?? '—'],
          ['Yuborildi',    $giftCertificate->sent_at?->format('d.m.Y H:i') ?? '—'],
          ['Muddati',      $giftCertificate->expires_at?->format('d.m.Y') ?? 'Muddatsiz'],
          ['Ishlatildi',   $giftCertificate->used_at?->format('d.m.Y H:i') ?? '—'],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:12px;font-weight:500;color:var(--p-text);
                       font-family:'JetBrains Mono',monospace">{{ $v }}</span>
        </div>
        @endforeach

        @if($giftCertificate->message)
        <div style="margin-top:12px;padding:10px;background:var(--p-elevated);
                    border-radius:7px;border-left:3px solid var(--p-accent)">
          <div style="font-size:10px;color:var(--p-hint);margin-bottom:4px">
            Shaxsiy xabar:
          </div>
          <div style="font-size:13px;color:var(--p-muted);font-style:italic">
            "{{ $giftCertificate->message }}"
          </div>
        </div>
        @endif
      </div>
    </div>

  </div>

  {{-- ── O'ng: Sotib olgan + Qabul qilgan ───────────────── --}}
  <div class="xl:col-span-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

      {{-- Sotib olgan --}}
      <div class="">
        <div class="p-card h-100 fade-up">
          <div class="p-card-header">
            <div class="p-card-title">
              <i class="bi bi-person-fill mr-1" style="color:var(--p-info)"></i>
              Sotib olgan
            </div>
            <span class="s-pill info" style="font-size:10px">Buyer</span>
          </div>
          <div style="padding:16px 18px">
            @if($giftCertificate->buyer)
            @php $buyer = $giftCertificate->buyer; @endphp
            <div class="flex items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;
                          flex-shrink:0;background:linear-gradient(135deg,var(--p-info),#0ea5e9);
                          display:flex;align-items:center;justify-content:center;
                          font-size:16px;font-weight:700;color:#fff">
                @if($buyer->avatar)
                  <img src="{{ asset('storage/'.$buyer->avatar) }}"
                       style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($buyer->name,0,1)) }}
                @endif
              </div>
              <div>
                <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                  {{ $buyer->name }} {{ $buyer->lastname }}
                </div>
                <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  {{ $buyer->phone_number }}
                </div>
              </div>
            </div>
            <a href="{{ route('admin.users.show',$buyer->id) }}"
               class="btn-p ghost sm" style="width:100%;justify-content:center">
              <i class="bi bi-person-lines-fill"></i> Profil
            </a>
            @else
            <div style="text-align:center;padding:20px;color:var(--p-hint)">
              <i class="bi bi-person-dash" style="font-size:24px;display:block;margin-bottom:6px"></i>
              Foydalanuvchi topilmadi
            </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Qabul qiluvchi --}}
      <div class="">
        <div class="p-card h-100 fade-up">
          <div class="p-card-header">
            <div class="p-card-title">
              <i class="bi bi-gift-fill mr-1" style="color:var(--p-success)"></i>
              Qabul qiluvchi
            </div>
            <span class="s-pill {{ $giftCertificate->recipient ? 'success' : 'muted' }}"
                  style="font-size:10px">
              {{ $giftCertificate->recipient ? 'Ro\'yxatdan o\'tgan' : 'Kutilmoqda' }}
            </span>
          </div>
          <div style="padding:16px 18px">
            @if($giftCertificate->recipient)
            @php $rec = $giftCertificate->recipient; @endphp
            <div class="flex items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;
                          flex-shrink:0;background:linear-gradient(135deg,var(--p-success),#059669);
                          display:flex;align-items:center;justify-content:center;
                          font-size:16px;font-weight:700;color:#fff">
                @if($rec->avatar)
                  <img src="{{ asset('storage/'.$rec->avatar) }}"
                       style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($rec->name,0,1)) }}
                @endif
              </div>
              <div>
                <div style="font-size:14px;font-weight:600;color:var(--p-text)">
                  {{ $rec->name }} {{ $rec->lastname }}
                </div>
                <div style="font-size:12px;color:var(--p-hint);font-family:'JetBrains Mono',monospace">
                  {{ $rec->phone_number }}
                </div>
              </div>
            </div>
            <a href="{{ route('admin.users.show',$rec->id) }}"
               class="btn-p ghost sm" style="width:100%;justify-content:center">
              <i class="bi bi-person-lines-fill"></i> Profil
            </a>
            @else
            {{-- Hali ishlatilmagan —  yuborish ma'lumotlari --}}
            <div style="display:flex;flex-direction:column;gap:10px">
              @if($giftCertificate->recipient_name || $giftCertificate->recipient_phone)
              @foreach([
                ['Ism',    $giftCertificate->recipient_name    ?? '—'],
                ['Telefon', $giftCertificate->recipient_phone  ?? '—'],
              ] as [$k,$v])
              <div>
                <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;
                            letter-spacing:.07em;margin-bottom:2px">{{ $k }}</div>
                <div style="font-size:13px;font-weight:500;color:var(--p-text);
                            font-family:'JetBrains Mono',monospace">{{ $v }}</div>
              </div>
              @endforeach
              @endif

              @if($giftCertificate->status === 'active' && $giftCertificate->expires_at)
              <div style="padding:10px;background:{{ $giftCertificate->is_expired ? 'var(--p-danger-d)' : 'var(--p-warning-d)' }};
                          border-radius:7px;font-size:12px;
                          color:{{ $giftCertificate->is_expired ? 'var(--p-danger)' : 'var(--p-warning)' }}">
                <i class="bi bi-{{ $giftCertificate->is_expired ? 'x-circle' : 'clock' }} mr-1"></i>
                @if($giftCertificate->is_expired)
                  Muddati o'tdi — bekor qilinadi
                @else
                  Ishlatish muddati: {{ $giftCertificate->expires_at->format('d.m.Y') }}
                  ({{ $giftCertificate->expires_at->diffForHumans() }})
                @endif
              </div>
              @elseif($giftCertificate->status === 'pending_payment')
              <div style="padding:10px;background:var(--p-elevated);border-radius:7px;
                          font-size:12px;color:var(--p-hint)">
                <i class="bi bi-hourglass mr-1"></i>
                To'lov kutilmoqda
              </div>
              @endif
            </div>
            @endif
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection
