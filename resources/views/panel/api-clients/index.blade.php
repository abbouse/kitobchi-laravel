@extends('panel.layouts.panel')
@section('title', 'API Mijozlar')
@section('page-title', 'API Mijozlar')

@section('content')

<div class="d-flex align-items-start justify-content-between mb-4 fade-up">
  <div>
    <h1 class="page-title">API Mijozlar</h1>
    <p class="page-sub">Tizimga kirish uchun API kalitlarni boshqarish</p>
  </div>
  <a href="{{ route('panel.api-clients.create') }}" class="btn-p primary">
    <i class="bi bi-plus-lg"></i> Yangi mijoz
  </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4 fade-up">
  @foreach([
    [App\Models\ApiClient::count(),             'Jami',     'accent',  'bi-key'],
    [App\Models\ApiClient::where('is_active',1)->count(), 'Faol', 'success', 'bi-check-circle'],
    [App\Models\ApiClient::where('is_active',0)->count(), 'Nofaol','danger','bi-x-circle'],
  ] as [$v,$l,$c,$i])
  <div class="col-4">
    <div class="p-card d-flex align-items-center gap-3" style="padding:14px">
      <div style="width:36px;height:36px;border-radius:9px;flex-shrink:0;font-size:16px;
                  background:var(--p-{{ $c }}-d,var(--p-elevated));color:var(--p-{{ $c }});
                  display:flex;align-items:center;justify-content:center">
        <i class="bi {{ $i }}"></i>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;font-family:'DM Mono',monospace;color:var(--p-text)">
          {{ $v }}
        </div>
        <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.07em">
          {{ $l }}
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Table --}}
<div class="p-card fade-up">
  <div class="table-responsive">
    <table class="p-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Nomi</th>
          <th>App ID</th>
          <th>App Secret</th>
          <th>Huquqlar</th>
          <th>Holat</th>
          <th>Yaratildi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($clients as $c)
        <tr>
          <td style="font-family:'DM Mono',monospace;color:var(--p-accent)">#{{ $c->id }}</td>

          <td>
            <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $c->name }}</div>
          </td>

          <td>
            <div class="d-flex align-items-center gap-2">
              <code style="font-size:12px;color:var(--p-accent);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'DM Mono',monospace">
                {{ $c->app_id }}
              </code>
              <button onclick="copyText('{{ $c->app_id }}')"
                      class="btn-p ghost sm" title="Nusxalash">
                <i class="bi bi-copy" style="font-size:11px"></i>
              </button>
            </div>
          </td>

          <td>
            <div class="d-flex align-items-center gap-2">
              <code id="secret-{{ $c->id }}"
                    style="font-size:12px;color:var(--p-muted);background:var(--p-elevated);
                           padding:3px 8px;border-radius:5px;font-family:'DM Mono',monospace;
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
            <div class="d-flex flex-wrap gap-1">
              @foreach($abilities ?? [] as $ab)
              <span class="s-pill accent" style="font-size:9.5px">{{ $ab }}</span>
              @endforeach
            </div>
          </td>

          <td>
            <form method="POST"
                  action="{{ route('panel.api-clients.toggle',$c) }}"
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
                     font-family:'DM Mono',monospace">
            {{ $c->created_at?->format('d.m.Y') }}
          </td>

          <td>
            <div class="d-flex gap-1">
              <a href="{{ route('panel.api-clients.edit',$c) }}"
                 class="btn-p ghost sm"><i class="bi bi-pencil"></i></a>

              <form method="POST"
                    action="{{ route('panel.api-clients.regenerate',$c) }}"
                    onsubmit="return confirm('Eski secret kalit endi ishlamaydi. Davom etilsinmi?')">
                @csrf @method('PATCH')
                <button class="btn-p ghost sm" title="Yangi kalit yaratish"
                        style="color:var(--p-warning)">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
              </form>

              <form method="POST"
                    action="{{ route('panel.api-clients.destroy',$c) }}"
                    onsubmit="return confirm('O\'chirilsinmi?')">
                @csrf @method('DELETE')
                <button class="btn-p danger sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center;padding:40px;color:var(--p-hint)">
            <i class="bi bi-key" style="font-size:28px;display:block;margin-bottom:8px"></i>
            API mijozlar yo'q
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

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