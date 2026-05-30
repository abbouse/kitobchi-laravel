@extends('a122.layouts.admin')
@section('title', 'API Client Audit Loglar')
@section('page-title', 'API Client Audit Loglar')

@section('content')
<div class="space-y-4">
  <x-a122.page-header back-href="{{ route('admin.api-clients.index') }}">
    <x-slot name="heading">Audit loglar</x-slot>
    <x-slot name="meta">Client API so‘rovlari, statuslari va tezligi shu yerda ko‘rinadi.</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.api-clients.logs.export', request()->query()) }}" class="btn-p ghost">CSV eksport</a>
    </x-slot>
  </x-a122.page-header>

  <section class="card-panel fade-up">
    <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
      <div>
        <label class="p-form-label">Client</label>
        <select name="client_id" class="p-form-control">
          <option value="">Barchasi</option>
          @foreach($clients as $client)
            <option value="{{ $client->id }}" {{ (string) request('client_id') === (string) $client->id ? 'selected' : '' }}>
              {{ $client->name }} (#{{ $client->id }})
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="p-form-label">Method</label>
        <select name="method" class="p-form-control">
          <option value="">Barchasi</option>
          @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
            <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="p-form-label">Status guruhi</label>
        <select name="status_group" class="p-form-control">
          <option value="">Barchasi</option>
          @foreach(['2xx', '4xx', '5xx'] as $group)
            <option value="{{ $group }}" {{ request('status_group') === $group ? 'selected' : '' }}>{{ $group }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="p-form-label">Path</label>
        <input type="text" name="path" class="p-form-control" value="{{ request('path') }}" placeholder="/api/v1/client/products">
      </div>

      <div>
        <label class="p-form-label">Sana dan</label>
        <input type="date" name="date_from" class="p-form-control" value="{{ request('date_from') }}">
      </div>

      <div>
        <label class="p-form-label">Sana gacha</label>
        <input type="date" name="date_to" class="p-form-control" value="{{ request('date_to') }}">
      </div>

      <div class="md:col-span-2 xl:col-span-6 flex flex-wrap gap-2">
        <button type="submit" class="btn-p primary">Filtrlash</button>
        <a href="{{ route('admin.api-clients.logs') }}" class="btn-p ghost">Tozalash</a>
      </div>
    </form>
  </section>

  <section class="card-panel fade-up">
    <div class="table-responsive kc-twrap">
      <table class="table data-table align-middle mb-0">
        <thead>
          <tr>
            <th>Vaqt</th>
            <th>Client</th>
            <th>Method</th>
            <th>Path</th>
            <th>Status</th>
            <th>Davomiylik</th>
            <th>IP</th>
            <th>User-Agent</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            <tr>
              <td style="white-space:nowrap;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-hint)">
                {{ $log->created_at?->format('d.m.Y H:i:s') }}
              </td>
              <td>
                <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $log->client?->name ?? '—' }}</div>
                <div style="font-size:11px;color:var(--p-hint)">{{ $log->client?->app_id }}</div>
              </td>
              <td><span class="s-pill accent">{{ $log->method }}</span></td>
              <td><code style="font-size:12px">{{ $log->path }}</code></td>
              <td>
                <span class="s-pill {{ $log->status_code >= 500 ? 'danger' : ($log->status_code >= 400 ? 'warning' : 'success') }}">
                  {{ $log->status_code }}
                </span>
              </td>
              <td style="white-space:nowrap">{{ $log->duration_ms }} ms</td>
              <td style="white-space:nowrap">{{ $log->ip_address ?: '—' }}</td>
              <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $log->user_agent }}">
                {{ $log->user_agent ?: '—' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;padding:36px;color:var(--p-hint)">Loglar topilmadi</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  @if($logs->hasPages())
    <div>{{ $logs->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
