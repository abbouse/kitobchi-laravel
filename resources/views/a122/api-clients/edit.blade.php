@extends('a122.layouts.admin')
@section('title', 'Tahrirlash: '.$apiClient->name)
@section('page-title', 'API mijoz tahrirlash')

@section('content')
<div class="space-y-4">
  <x-a122.page-header back-href="{{ route('admin.api-clients.index') }}">
    <x-slot name="heading">{{ $apiClient->name }}</x-slot>
    <x-slot name="meta">Mijoz holati, huquqlari va secret boshqaruvi shu sahifada.</x-slot>
    <x-slot name="actions">
      <a href="{{ route('admin.api-clients.logs', ['client_id' => $apiClient->id]) }}" class="btn-p ghost">To‘liq audit</a>
    </x-slot>
  </x-a122.page-header>

  <form method="POST" action="{{ route('admin.api-clients.update', $apiClient) }}">
    @csrf
    @method('PUT')
    @include('a122.api-clients._form', compact('apiClient'))
  </form>

  <section class="card-panel fade-up">
    <div class="dash-card-head">
      <div class="dash-card-title">So‘nggi audit loglar</div>
    </div>

    <div class="table-responsive kc-twrap">
      <table class="table data-table align-middle mb-0">
        <thead>
          <tr>
            <th>Vaqt</th>
            <th>Method</th>
            <th>Path</th>
            <th>Status</th>
            <th>Davomiylik</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentLogs as $log)
            <tr>
              <td style="white-space:nowrap;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-hint)">
                {{ $log->created_at?->format('d.m.Y H:i:s') }}
              </td>
              <td><span class="s-pill accent">{{ $log->method }}</span></td>
              <td>
                <code style="font-size:12px">{{ $log->path }}</code>
              </td>
              <td>
                <span class="s-pill {{ $log->status_code >= 500 ? 'danger' : ($log->status_code >= 400 ? 'warning' : 'success') }}">
                  {{ $log->status_code }}
                </span>
              </td>
              <td style="white-space:nowrap">{{ $log->duration_ms }} ms</td>
              <td style="white-space:nowrap">{{ $log->ip_address ?: '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center;padding:32px;color:var(--p-hint)">
                Hali request loglar yo‘q
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
