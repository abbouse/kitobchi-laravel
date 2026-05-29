@extends('a122.layouts.admin')
@section('title', 'Push bildirishnomalar')
@section('page-title', 'Push bildirishnomalar')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header eyebrow="Operations" title="Push bildirishnomalar" subtitle="{{ $notifications->total() }} ta yozuv">
    <a href="{{ route('admin.push.create') }}" class="btn-p primary">
      <i class="bi bi-send"></i>
      <span>Yuborish</span>
    </a>
  </x-admin.page-header>

  <div class="row g-3">
    @foreach([
      [$counts['all'] ?? 0, 'Jami', 'bi-bell', 'primary'],
      [$counts['users'] ?? 0, 'Userlar', 'bi-people', 'info'],
      [$counts['business'] ?? 0, 'Sellerlar', 'bi-shop-window', 'warning'],
      [$counts['courier'] ?? 0, 'Kuryerlar', 'bi-bicycle', 'success'],
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

  <x-admin.section-card title="Pushlar jadvali" :meta="$notifications->total() . ' ta yozuv'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Sarlavha</th>
            <th>Matn</th>
            <th>Qabul qiluvchi</th>
            <th>Sana</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($notifications as $notification)
            @php $target = $targets[$notification->who] ?? ['label' => $notification->who, 'color' => 'secondary', 'icon' => 'bi-bell']; @endphp
            <tr>
              <td class="text-secondary">#{{ $notification->id }}</td>
              <td class="fw-semibold">{{ $notification->name }}</td>
              <td class="text-secondary text-truncate" style="max-width: 24rem;">{{ $notification->description }}</td>
              <td>
                <span class="badge rounded-pill text-bg-info-subtle border border-info-subtle text-info-emphasis">
                  <i class="bi {{ $target['icon'] }} me-1"></i>{{ $target['label'] }}
                </span>
              </td>
              <td class="text-secondary text-nowrap">{{ $notification->created_at?->format('d.m.Y H:i') }}</td>
              <td class="text-end">
                <form method="POST" action="{{ route('admin.push.destroy', $notification) }}" onsubmit="return confirm('O‘chirilsinmi?')" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center py-5 text-secondary">Bildirishnoma topilmadi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  @if($notifications->hasPages())
    <div>{{ $notifications->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
