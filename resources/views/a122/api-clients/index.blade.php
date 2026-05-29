@extends('a122.layouts.admin')
@section('title', 'API mijozlar')
@section('page-title', 'API mijozlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Admin" title="API mijozlar" subtitle="{{ $clients->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 26rem;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <i class="bi bi-search kc-search__icon"></i>
      <input type="search" name="search" value="{{ request('search') }}" placeholder="Nomi, App ID yoki huquq" class="form-control">
    </form>
    <a href="{{ route('admin.api-clients.logs') }}" class="btn btn-light border">Audit</a>
    <a href="{{ route('admin.api-clients.docs') }}" class="btn btn-light border">Docs</a>
    <a href="{{ route('admin.api-clients.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  <div class="kc-filter-card">
    <div class="nav nav-pills flex-wrap">
      @foreach([
        'active' => ['Faol', $counts['active'] ?? 0],
        'inactive' => ['Nofaol', $counts['inactive'] ?? 0],
        'all' => ['Barchasi', $counts['all'] ?? 0],
      ] as $key => [$label, $count])
        <a href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
          {{ $label }}
          <span class="badge rounded-pill {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ number_format($count) }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <div class="row g-3">
    @foreach([
      ['Jami', $counts['all'] ?? 0, 'bi-key', 'primary'],
      ['Faol', $counts['active'] ?? 0, 'bi-check-circle', 'success'],
      ['Nofaol', $counts['inactive'] ?? 0, 'bi-x-circle', 'danger'],
    ] as [$label, $value, $icon, $tone])
      <div class="col-12 col-md-4">
        <div class="a122-stat-tile h-100">
          <div class="a122-stat-tile__icon bg-{{ $tone }}-subtle text-{{ $tone }}">
            <i class="bi {{ $icon }}"></i>
          </div>
          <div>
            <div class="a122-stat-tile__value">{{ number_format($value) }}</div>
            <div class="a122-stat-tile__label">{{ $label }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <x-admin.section-card title="Integratsiyalar jadvali" :meta="$clients->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nomi</th>
            <th>App ID</th>
            <th>Secret</th>
            <th>Huquqlar</th>
            <th>Limit</th>
            <th>Holat</th>
            <th>Yaratildi</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($clients as $client)
            @php
              $abilities = is_string($client->abilities)
                ? json_decode($client->abilities, true)
                : (is_array($client->abilities) ? $client->abilities : []);
            @endphp
            <tr>
              <td class="text-secondary">#{{ $client->id }}</td>
              <td class="fw-semibold">{{ $client->name }}</td>
              <td>
                <div class="d-inline-flex align-items-center gap-2">
                  <code class="kc-inline-code">{{ $client->app_id }}</code>
                  <button type="button" onclick="copyText('{{ $client->app_id }}')" class="btn btn-sm btn-light border kc-table-action" title="Nusxalash">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </td>
              <td>
                <div class="d-inline-flex align-items-center gap-2">
                  <code id="secret-{{ $client->id }}" class="kc-inline-code text-secondary">{{ str_repeat('•', 12) }}{{ substr($client->app_secret, -4) }}</code>
                  <button type="button" onclick="toggleSecret({{ $client->id }}, '{{ addslashes($client->app_secret) }}')" class="btn btn-sm btn-light border kc-table-action" id="eye-{{ $client->id }}" title="Ko‘rsatish">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button type="button" onclick="copyText('{{ addslashes($client->app_secret) }}')" class="btn btn-sm btn-light border kc-table-action" title="Nusxalash">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  @forelse($abilities ?? [] as $ability)
                    <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $ability }}</span>
                  @empty
                    <span class="text-secondary">—</span>
                  @endforelse
                </div>
              </td>
              <td class="small text-secondary text-nowrap">
                {{ $client->rate_limit_per_second ?? 8 }}/s · {{ $client->rate_limit_per_minute ?? 240 }}/min
              </td>
              <td>
                <form method="POST" action="{{ route('admin.api-clients.toggle', $client) }}" class="d-inline">
                  @csrf
                  @method('PATCH')
                  <button class="badge rounded-pill border {{ $client->is_active ? 'text-bg-success-subtle border-success-subtle text-success-emphasis' : 'text-bg-danger-subtle border-danger-subtle text-danger-emphasis' }}" title="Holatni o‘zgartirish">
                    {{ $client->is_active ? 'Faol' : 'Nofaol' }}
                  </button>
                </form>
              </td>
              <td class="text-secondary text-nowrap">{{ $client->created_at?->format('d.m.Y') }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.api-clients.edit', $client) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.api-clients.regenerate', $client) }}" onsubmit="return confirm('Eski secret kalit endi ishlamaydi. Davom etilsinmi?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-sm btn-light border kc-table-action" title="Yangi kalit">
                      <i class="bi bi-arrow-repeat"></i>
                    </button>
                  </form>
                  <form method="POST" action="{{ route('admin.api-clients.destroy', $client) }}" onsubmit="return confirm('O‘chirilsinmi?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center py-5 text-secondary">API mijoz topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($clients->hasPages())
    <div>{{ $clients->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
const secretVisible = {};

function toggleSecret(id, secret) {
  const el = document.getElementById('secret-' + id);
  const eye = document.getElementById('eye-' + id).querySelector('i');
  if (secretVisible[id]) {
    el.textContent = '•'.repeat(12) + secret.slice(-4);
    eye.className = 'bi bi-eye';
    secretVisible[id] = false;
  } else {
    el.textContent = secret;
    eye.className = 'bi bi-eye-slash';
    secretVisible[id] = true;
  }
}

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => {
    const toast = document.createElement('div');
    toast.textContent = 'Nusxalandi';
    toast.className = 'kc-copy-toast';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 1800);
  });
}
</script>
@endpush
