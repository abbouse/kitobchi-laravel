@extends('panel.layouts.panel')
@section('title', 'Promokodlar')
@section('page-title', 'Promokodlar')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
  <div class="tab-pills">
    @foreach([['all','Barchasi'],['active','Aktiv'],['expired','Muddati o\'tgan']] as [$k,$l])
    <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
       class="tab-pill {{ $tab===$k?'active':'' }}">
      {{ $l }} <span class="tab-badge">{{ $counts[$k] }}</span>
    </a>
    @endforeach
  </div>
  <a href="{{ route('panel.promocodes.create') }}" class="btn-p">
    <i class="bi bi-plus-lg"></i> Yangi promokod
  </a>
</div>

<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="Kod bo'yicha..."
           value="{{ request('search') }}" style="width:200px">
    <select name="type" class="p-form-control" style="width:150px">
      <option value="">Barcha tur</option>
      <option value="percent" {{ request('type')==='percent'?'selected':'' }}>Foiz (%)</option>
      <option value="fixed"   {{ request('type')==='fixed'?'selected':'' }}>Miqdor (UZS)</option>
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.promocodes.index') }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Kod</th>
          <th>Tur</th>
          <th>Miqdor</th>
          <th>Min. buyurtma</th>
          <th>Limit / Ishlatildi</th>
          <th>Muddat</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($promocodes as $p)
        @php
          $expired = $p->expires_at <= now();
          $full    = $p->usesLimit > 0 && $p->usedCount >= $p->usesLimit;
        @endphp
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent)">{{ $p->id }}</td>
          <td>
            <code style="font-family:'DM Mono',monospace;font-size:14px;font-weight:700;
                         color:var(--p-text);background:var(--p-elevated);
                         padding:3px 10px;border-radius:6px;letter-spacing:.05em">
              {{ $p->code }}
            </code>
          </td>
          <td>
            <span class="s-pill {{ $p->type==='percent'?'info':'accent' }}">
              {{ $p->type==='percent' ? 'Foiz (%)' : 'Miqdor (UZS)' }}
            </span>
          </td>
          <td style="font-family:'DM Mono',monospace;font-weight:700;font-size:14px;color:var(--p-text)">
            {{ $p->type==='percent' ? $p->amount.'%' : number_format($p->amount).' UZS' }}
          </td>
          <td style="font-size:12px;color:var(--p-muted)">
            {{ $p->min_order_amount > 0 ? number_format($p->min_order_amount).' UZS' : '—' }}
          </td>
          <td>
            @if($p->usesLimit > 0)
              <div style="font-family:'DM Mono',monospace;font-size:12px">
                <span style="color:{{ $full?'var(--p-danger)':'var(--p-text)' }}">{{ $p->usedCount }}</span>
                / {{ $p->usesLimit }}
              </div>
              <div class="dash-prog-track" style="margin-top:4px">
                <div class="dash-prog-fill" style="width:{{ min(round($p->usedCount/$p->usesLimit*100),100) }}%;
                     background:{{ $full?'var(--p-danger)':'var(--p-accent)' }}"></div>
              </div>
            @else
              <span style="font-size:12px;color:var(--p-hint)">{{ $p->usedCount }} / ∞</span>
            @endif
          </td>
          <td style="font-size:12px;white-space:nowrap">
            <span style="color:{{ $expired?'var(--p-danger)':'var(--p-muted)' }}">
              {{ \Carbon\Carbon::parse($p->expires_at)->format('d.m.Y H:i') }}
            </span>
            @if($expired)
              <div style="font-size:10px;color:var(--p-danger)">Muddati o'tgan</div>
            @endif
          </td>
          <td>
            <span class="s-pill {{ $p->status && !$expired && !$full ? 'success' : 'muted' }}">
              {{ $p->status && !$expired && !$full ? 'Aktiv' : 'Nofaol' }}
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="{{ route('panel.promocodes.show', $p) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
              <a href="{{ route('panel.promocodes.edit', $p) }}" class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>
              <form method="POST" action="{{ route('panel.promocodes.destroy', $p) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-ticket-perforated" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Promokodlar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($promocodes->hasPages())
  <div class="p-pagination">{{ $promocodes->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection