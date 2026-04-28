@extends('a122.layouts.admin')
@section('title', 'API Mijozlar')
@section('page-title', 'API Mijozlar')

@section('content')

<x-a122.page-header>
  <x-slot name="heading">API mijozlar</x-slot>
  <x-slot name="meta">Tashqi ilovalar va servislar uchun App ID, Secret hamda ruxsat darajalari shu modulda boshqariladi.</x-slot>
  <x-slot name="actions">
    <a href="{{ route('admin.api-clients.logs') }}" class="btn-p ghost">Audit loglar</a>
    <a href="{{ route('admin.api-clients.docs') }}" class="btn-p ghost">Docs</a>
    <a href="{{ route('admin.api-clients.create') }}" class="btn-p primary">Yangi mijoz</a>
  </x-slot>
</x-a122.page-header>

<div class="tab-pills fade-up mb-3">
  @foreach([
    'active' => ['Faol', $counts['active'] ?? 0],
    'inactive' => ['Nofaol', $counts['inactive'] ?? 0],
    'all' => ['Barchasi', $counts['all'] ?? 0],
  ] as $key => [$label, $count])
    <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="tab-pill {{ $tab === $key ? 'active' : '' }}">
      {{ $label }} <span>{{ $count }}</span>
    </a>
  @endforeach
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 mb-4">
  @foreach([
    [App\Models\ApiClient::count(),             'Jami',     'accent',  'bi-key'],
    [App\Models\ApiClient::where('is_active',1)->count(), 'Faol', 'success', 'bi-check-circle'],
    [App\Models\ApiClient::where('is_active',0)->count(), 'Nofaol','danger','bi-x-circle'],
  ] as [$v,$l,$c,$i])
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
  @endforeach
</div>

<div class="a122-index-header">
  <div>
    <div class="a122-index-header__title">Integratsiyalar ro'yxati</div>
    <div class="a122-index-header__meta">{{ $clients->total() }} ta API mijoz topildi</div>
  </div>
  <div class="a122-index-header__actions">
    <form method="GET" class="a122-index-search-form">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Nomi, App ID, secret yoki huquq bo'yicha qidiring">
    </form>
  </div>
</div>

<div class="p-card fade-up">
  <div class="table-responsive kc-twrap">
    <table class="p-table" data-index-grid>
      <thead>
        <tr>
          <th>#</th>
          <th>Nomi</th>
          <th>App ID</th>
          <th>App Secret</th>
          <th>Huquqlar</th>
          <th>Limit</th>
          <th>Holat</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($clients as $c)
        <tr>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--p-accent)">#{{ $c->id }}</td>

          <td>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $c->name }}</div>
          </td>

          <td>
            <div class="flex items-center gap-2">
              <code style="font-size:12px;color:var(--p-accent);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'JetBrains Mono',monospace">
                {{ $c->app_id }}
              </code>
              <button onclick="copyText('{{ $c->app_id }}')"
                      class="btn-p ghost sm" title="Nusxalash">
                <i class="bi bi-copy" style="font-size:11px"></i>
              </button>
            </div>
          </td>

          <td>
            <div class="flex items-center gap-2">
              <code id="secret-{{ $c->id }}"
                    style="font-size:12px;color:var(--p-muted);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'JetBrains Mono',monospace;
                           letter-spacing:.05em">
                {{ str_repeat('•', 12) }}{{ substr($c->app_secret, -4) }}
              </code>
              <button onclick="toggleSecret({{ $c->id }}, '{{ addslashes($c->app_secret) }}')"
                      class="btn-p ghost sm" id="eye-{{ $c->id }}" title="Ko'rsatish">
                <i class="bi bi-eye" style="font-size:11px"></i>
              </button>
              <button onclick="copyText('{{ addslashes($c->app_secret) }}')"
                      class="btn-p ghost sm" title="Nusxalash">
                <i class="bi bi-copy" style="font-size:11px"></i>
              </button>
            </div>
          </td>

          <td>
            @php
              $abilities = is_string($c->abilities)
                ? json_decode($c->abilities, true)
                : (is_array($c->abilities) ? $c->abilities : []);
            @endphp
            <div class="flex flex-wrap gap-1">
              @foreach($abilities ?? [] as $ab)
              <span class="s-pill accent" style="font-size:9.5px">{{ $ab }}</span>
              @endforeach
            </div>
          </td>

          <td style="white-space:nowrap">
            <div style="font-size:12px;color:var(--p-text)">S: {{ $c->rate_limit_per_second ?? 8 }}</div>
            <div style="font-size:11px;color:var(--p-hint)">D: {{ $c->rate_limit_per_minute ?? 240 }}</div>
          </td>

          <td>
            <form method="POST"
                  action="{{ route('admin.api-clients.toggle',$c) }}"
                  style="display:inline">
              @csrf @method('PATCH')
              <button class="s-pill {{ $c->is_active ? 'success' : 'danger' }}"
                      style="font-size:10px;border:none;cursor:pointer;padding:3px 10px"
                      title="Holat o'zgartirish">
                {{ $c->is_active ? '● Faol' : '○ Nofaol' }}
              </button>
            </form>
          </td>

          <td style="font-size:11px;color:var(--p-hint);white-space:nowrap;
                     font-family:'JetBrains Mono',monospace">
            {{ $c->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="flex gap-1">
              <a href="{{ route('admin.api-clients.edit',$c) }}"
                 class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>

              <form method="POST"
                    action="{{ route('admin.api-clients.regenerate',$c) }}"
                    onsubmit="return confirm('Eski secret kalit endi ishlamaydi. Davom etilsinmi?')">
                @csrf @method('PATCH')
                <button class="btn-p ghost sm" title="Yangi kalit yaratish"
                        style="color:var(--p-warning)">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
              </form>

              <form method="POST"
                    action="{{ route('admin.api-clients.destroy',$c) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-key" style="font-size:28px;display:block;margin-bottom:8px"></i>
            API mijozlar yo'q
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if($clients->hasPages())
  <div class="mt-4">{{ $clients->links('a122.partials.pagination') }}</div>
@endif

@endsection

@push('scripts')
<script>
const secretVisible = {};

function toggleSecret(id, secret) {
  const el  = document.getElementById('secret-' + id);
  const eye = document.getElementById('eye-' + id).querySelector('i');
  if (secretVisible[id]) {
    el.textContent = '•'.repeat(12) + secret.slice(-4);
    eye.className  = 'bi bi-eye';
    secretVisible[id] = false;
  } else {
    el.textContent = secret;
    eye.className  = 'bi bi-eye-slash';
    secretVisible[id] = true;
  }
}

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Toast
    const t = document.createElement('div');
    t.textContent = 'Nusxalandi!';
    t.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:9999;
      background:var(--p-surface);border:1px solid var(--p-border);
      padding:8px 16px;border-radius:8px;font-size:13px;
      color:var(--p-success);box-shadow:0 4px 20px rgba(0,0,0,.15)`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 1800);
  });
}
</script>
@endpush
