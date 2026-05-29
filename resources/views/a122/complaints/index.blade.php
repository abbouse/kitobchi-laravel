@extends('a122.layouts.admin')
@section('title', 'Shikoyatlar')
@section('page-title', 'Shikoyatlar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Operations" title="Shikoyatlar" subtitle="{{ $reports->total() }} ta yozuv" />

  <div class="row g-3">
    @foreach([
      [$counts['pending'] ?? 0, 'Kutilmoqda', 'bi-hourglass-split', 'warning'],
      [$counts['reviewed'] ?? 0, 'Ko‘rilgan', 'bi-check2-circle', 'success'],
      [$counts['dismissed'] ?? 0, 'Rad etilgan', 'bi-slash-circle', 'secondary'],
      [($counts['pending'] ?? 0) + ($counts['reviewed'] ?? 0) + ($counts['dismissed'] ?? 0), 'Jami', 'bi-flag', 'primary'],
    ] as [$value, $label, $icon, $tone])
      <div class="col-6 col-xl-3">
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

  <x-admin.section-card title="Shikoyatlar jadvali" :meta="$reports->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Shikoyatchi</th>
            <th>Tur / ID</th>
            <th>Sabab</th>
            <th>Izoh</th>
            <th>Holat</th>
            <th>Vaqt</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $report)
            @php
              $statusClass = match($report->status) {
                'reviewed' => 'text-bg-success-subtle border border-success-subtle text-success-emphasis',
                'dismissed' => 'text-bg-secondary',
                default => 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis',
              };
              $statusLabel = match($report->status) {
                'reviewed' => 'Ko‘rildi',
                'dismissed' => 'Rad',
                default => 'Yangi',
              };
              $typeLabel = match($report->reportable_type) {
                'conversation_message' => 'Xabar',
                'book_club' => 'Book Club',
                default => $report->reportable_type,
              };
            @endphp
            <tr>
              <td class="text-secondary">#{{ $report->id }}</td>
              <td>
                @if($report->user)
                  <a href="{{ route('admin.users.show', $report->user_id) }}" class="fw-semibold text-decoration-none">
                    {{ $report->user->name }} {{ $report->user->lastname }}
                  </a>
                  <div class="small text-secondary">{{ $report->user->phone_number }}</div>
                @else
                  <span class="text-secondary">#{{ $report->user_id }}</span>
                @endif
              </td>
              <td>
                <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">{{ $typeLabel }}</span>
                <div class="small text-secondary">#{{ $report->reportable_id }}</div>
              </td>
              <td class="fw-semibold">{{ $report->reason }}</td>
              <td class="text-secondary text-truncate" style="max-width: 12rem;">{{ $report->comment ?: '—' }}</td>
              <td><span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
              <td class="text-secondary text-nowrap">{{ \Carbon\Carbon::parse($report->created_at)->format('d.m.Y H:i') }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center justify-content-end gap-1">
                  <a href="{{ route('admin.complaints.show', $report) }}" class="btn btn-sm btn-light border kc-table-action" title="Ko‘rish">
                    <i class="bi bi-eye"></i>
                  </a>
                  @if($report->status === 'pending')
                    <form method="POST" action="{{ route('admin.complaints.status', $report) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="status" value="reviewed">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Ko‘rildi">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.complaints.status', $report) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="status" value="dismissed">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Rad etish">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </form>
                  @endif
                  @if($report->status !== 'pending')
                    <form method="POST" action="{{ route('admin.complaints.status', $report) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="status" value="pending">
                      <button class="btn btn-sm btn-light border kc-table-action" title="Qayta ochish">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center py-5 text-secondary">Shikoyat topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($reports->hasPages())
    <div>{{ $reports->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
