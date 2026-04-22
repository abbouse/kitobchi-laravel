@extends('a122.layouts.admin')
@section('title', 'Promokod: '.$promocode->code)
@section('page-title', 'Promokod: '.$promocode->code)

@section('content')

<x-a122.page-header back-href="{{ route('admin.promocodes.index') }}">
  <x-slot name="heading">Promokod: {{ $promocode->code }}</x-slot>
  <x-slot name="meta">ID: #{{ $promocode->id }} · {{ $promocode->created_at?->format('d.m.Y') }}</x-slot>
</x-a122.page-header>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="xl:col-span-4">
    <div class="p-card mb-3">
      <div class="dash-card-body" style="text-align:center;padding:30px">
        <code style="font-family:'JetBrains Mono',monospace;font-size:26px;font-weight:700;
                     color:var(--p-accent);background:var(--p-elevated);
                     padding:12px 24px;border-radius:10px;letter-spacing:.1em;display:inline-block">
          {{ $promocode->code }}
        </code>
        <div class="mt-3">
          <span class="s-pill {{ $promocode->status && $promocode->expires_at > now() ? 'success' : 'danger' }}" style="font-size:13px;padding:5px 14px">
            {{ $promocode->status && $promocode->expires_at > now() ? 'Aktiv' : 'Nofaol' }}
          </span>
        </div>
      </div>
    </div>

    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Ma'lumotlar</div></div>
      <div class="dash-card-body">
        @foreach([
          ['Tur',       $promocode->type === 'percent' ? 'Foiz (%)' : 'Miqdor (UZS)'],
          ['Chegirma',  $promocode->type === 'percent' ? $promocode->amount.'%' : number_format($promocode->amount).' UZS'],
          ['Min. buyurtma', $promocode->min_order_amount > 0 ? number_format($promocode->min_order_amount).' UZS' : '—'],
          ['Limit',     $promocode->usesLimit ?: 'Cheksiz'],
          ['Ishlatildi', $promocode->usedCount.' marta'],
          ['Muddat',    \Carbon\Carbon::parse($promocode->expires_at)->format('d.m.Y H:i')],
          ["Qo'shildi", $promocode->created_at?->format('d.m.Y H:i')],
        ] as [$k,$v])
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--p-border)">
          <span style="font-size:12px;color:var(--p-hint)">{{ $k }}</span>
          <span style="font-size:13px;font-weight:500;color:var(--p-text)">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>

    @if($promocode->usesLimit > 0)
    <div class="p-card mb-3">
      <div class="dash-card-head"><div class="dash-card-title">Foydalanish</div></div>
      <div class="dash-card-body">
        @php $pct = min(round($promocode->usedCount / $promocode->usesLimit * 100), 100); @endphp
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:12px;color:var(--p-hint)">{{ $promocode->usedCount }} / {{ $promocode->usesLimit }}</span>
          <span style="font-size:12px;font-weight:600;color:var(--p-text)">{{ $pct }}%</span>
        </div>
        <div class="dash-prog-track" style="height:8px">
          <div class="dash-prog-fill" style="width:{{ $pct }}%;background:{{ $pct>=100?'var(--p-danger)':'var(--p-accent)' }}"></div>
        </div>
      </div>
    </div>
    @endif

    <a href="{{ route('admin.promocodes.edit', $promocode) }}" class="btn-p primary" style="width:100%;justify-content:center;margin-bottom:8px">
      <i class="bi bi-pencil"></i> Tahrirlash
    </a>
  </div>

  <div class="xl:col-span-8">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Foydalanish tarixi</div>
        <div class="dash-card-sub">{{ $histories->total() }} ta foydalanuvchi</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive kc-twrap">
          <table class="p-table">
            <thead>
              <tr><th>#</th><th>Foydalanuvchi</th><th>Telefon</th><th>Sana</th></tr>
            </thead>
            <tbody>
              @forelse($histories as $h)
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">{{ $h->id }}</td>
                <td>
                  @if($h->user)
                  <a href="{{ route('admin.users.show', $h->user_id) }}" style="color:var(--p-text);font-weight:500">
                    {{ $h->user->name }} {{ $h->user->lastname }}
                  </a>
                  @else
                    <span style="color:var(--p-hint)">ID: {{ $h->user_id }}</span>
                  @endif
                </td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-muted)">{{ $h->user?->phone_number ?? '—' }}</td>
                <td style="font-size:11px;color:var(--p-hint)">{{ \Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i') }}</td>
              </tr>
              @empty
              <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--p-hint)">Hali ishlatilmagan</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($histories->hasPages())
        {{ $histories->links('a122.partials.pagination') }}
        @endif
      </div>
    </div>
  </div>
</div>
@endsection