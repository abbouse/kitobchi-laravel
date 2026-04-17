@extends('panel.layouts.panel')
@section('title', 'Reklama moderatsiyasi')
@section('page-title', 'Reklamalar')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="tab-pills">
    @foreach([
      ['pending','Kutilmoqda','warning'],
      ['approved','Tasdiqlangan','success'],
      ['rejected','Rad etilgan','danger'],
      ['active','Faol','info'],
      ['expired','Muddati o\'tgan','muted'],
    ] as [$k,$l,$c])
    <a href="{{ request()->fullUrlWithQuery(['tab'=>$k,'page'=>1]) }}"
       class="tab-pill {{ $tab===$k?'active':'' }}">
      {{ $l }} <span class="tab-badge">{{ $counts[$k] }}</span>
    </a>
    @endforeach
  </div>
  <a href="{{ route('panel.seller-ads.settings') }}" class="btn-p ghost">
    <i class="bi bi-gear"></i> Narxlar
  </a>
</div>

<div class="filter-bar mb-3">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="search" name="search" class="p-form-control" placeholder="ID, sotuvchi ID..."
           value="{{ request('search') }}" style="width:200px">
    <select name="type" class="p-form-control" style="width:160px">
      <option value="">Barcha tur</option>
      @foreach($types as $t)
      <option value="{{ $t }}" {{ request('type')===$t?'selected':'' }}>{{ $t }}</option>
      @endforeach
    </select>
    <button class="btn-p" type="submit"><i class="bi bi-search"></i></button>
    <a href="{{ route('panel.seller-ads.index',['tab'=>$tab]) }}" class="btn-p ghost"><i class="bi bi-x"></i></a>
  </form>
</div>

<div class="p-card p-0">
  <div class="table-responsive">
    <table class="p-table" style="min-width:780px">
      <thead>
        <tr>
          <th>#</th>
          <th>Banner</th>
          <th>Sotuvchi</th>
          <th>Tur</th>
          <th>Harakat</th>
          <th>Summa</th>
          <th>Muddat</th>
          <th>Moderatsiya</th>
          <th>To'lov</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($ads as $ad)
        @php
          $modCls = match($ad->moderation) {
            'approved'=>'s-pill success','rejected'=>'s-pill danger',default=>'s-pill warning'
          };
          $modLbl = match($ad->moderation) {
            'approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan',default=>'Kutilmoqda'
          };
          $payCls = match($ad->paymentStatus) {
            'paid'=>'s-pill success','failed'=>'s-pill danger',default=>'s-pill warning'
          };
          $payLbl = match($ad->paymentStatus) {
            'paid'=>'To\'langan','failed'=>'Muvaffaqiyatsiz',default=>'Kutilmoqda'
          };
          $expired = $ad->expire_at && $ad->expire_at <= now();
        @endphp
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent);font-weight:600">#{{ $ad->id }}</td>
          <td>
            @if($ad->banner_img)
              <a href="{{ $ad->banner_img }}" target="_blank">
                <img src="{{ $ad->banner_img }}" style="width:72px;height:36px;border-radius:5px;object-fit:cover;border:1px solid var(--p-border)">
              </a>
            @else
              <div style="width:72px;height:36px;border-radius:5px;background:var(--p-elevated);display:flex;align-items:center;justify-content:center">
                <i class="bi bi-image" style="color:var(--p-hint)"></i>
              </div>
            @endif
          </td>
          <td>
            @if($ad->seller)
            <a href="{{ route('panel.sellers.show', $ad->seller_id) }}"
               style="font-size:13px;font-weight:500;color:var(--p-text)">
              {{ Str::limit($ad->seller->shop_name ?? $ad->seller_id, 18) }}
            </a>
            @else
              <span style="color:var(--p-hint)">#{{ $ad->seller_id }}</span>
            @endif
          </td>
          <td>
            <span class="s-pill muted" style="font-size:11px">{{ $ad->type }}</span>
          </td>
          <td style="font-size:11px;color:var(--p-hint)">
            {{ $ad->action }} → {{ $ad->product_type }} #{{ $ad->product_id }}
          </td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--p-text)">
            {{ number_format($ad->amount) }}
          </td>
          <td style="font-size:11px;white-space:nowrap">
            <span style="color:{{ $expired?'var(--p-danger)':'var(--p-muted)' }}">
              {{ $ad->expire_at ? \Carbon\Carbon::parse($ad->expire_at)->format('d.m.Y') : '—' }}
            </span>
            @if($expired)
              <div style="font-size:10px;color:var(--p-danger)">Muddati o'tgan</div>
            @endif
          </td>
          <td><span class="{{ $modCls }}">{{ $modLbl }}</span></td>
          <td><span class="{{ $payCls }}">{{ $payLbl }}</span></td>
          <td>
            <div class="d-flex gap-1">
              @if($ad->moderation === 'pending')
              <form method="POST" action="{{ route('panel.seller-ads.moderate', $ad) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button class="btn-p success sm" title="Tasdiqlash"><i class="bi bi-check-lg"></i></button>
              </form>
              <form method="POST" action="{{ route('panel.seller-ads.moderate', $ad) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <button class="btn-p danger sm" title="Rad etish"><i class="bi bi-x-lg"></i></button>
              </form>
              @endif
              <a href="{{ route('panel.seller-ads.show', $ad) }}" class="btn-p ghost sm"><i class="bi bi-eye"></i></a>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--p-hint)">
          <i class="bi bi-megaphone" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Reklamalar topilmadi
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($ads->hasPages())
  <div class="p-pagination">{{ $ads->links('panel.partials.pagination') }}</div>
  @endif
</div>
@endsection