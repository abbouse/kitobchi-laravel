@extends('a122.layouts.admin')
@section('title', 'Qidiruv tarixi')
@section('page-title', 'Qidiruv tarixi')

@section('content')
<div class="space-y-4">
  <x-a122.page-header>
    <x-slot name="heading">Qidiruv tarixi</x-slot>
    <x-slot name="meta">Foydalanuvchilar va guest sessiyalar qilgan qidiruvlar shu yerda ko‘rinadi.</x-slot>
  </x-a122.page-header>

  <section class="p-card fade-up">
    <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
      <div>
        <label class="p-form-label">Qidiruv</label>
        <input type="search" name="search" class="p-form-control" value="{{ request('search') }}" placeholder="Matn, natija, user yoki telefon">
      </div>

      <div>
        <label class="p-form-label">Turi</label>
        <select name="type" class="p-form-control">
          <option value="">Barchasi</option>
          @foreach(['book', 'author', 'stationery', 'tag'] as $type)
            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="p-form-label">Kim qidirgan</label>
        <select name="scope" class="p-form-control">
          <option value="">Barchasi</option>
          <option value="user" {{ request('scope') === 'user' ? 'selected' : '' }}>User</option>
          <option value="guest" {{ request('scope') === 'guest' ? 'selected' : '' }}>Guest</option>
        </select>
      </div>

      <div>
        <label class="p-form-label">Draft</label>
        <select name="draft" class="p-form-control">
          <option value="">Barchasi</option>
          <option value="0" {{ request('draft') === '0' ? 'selected' : '' }}>Yo‘q</option>
          <option value="1" {{ request('draft') === '1' ? 'selected' : '' }}>Ha</option>
        </select>
      </div>

      <div class="flex items-end gap-2">
        <button type="submit" class="btn-p primary">Filtrlash</button>
        <a href="{{ route('admin.search-history.index') }}" class="btn-p ghost">Tozalash</a>
      </div>
    </form>
  </section>

  <section class="p-card fade-up">
    <div class="table-responsive kc-twrap">
      <table class="p-table">
        <thead>
          <tr>
            <th>Vaqt</th>
            <th>Qidiruv matni</th>
            <th>Natija</th>
            <th>Turi</th>
            <th>Kim qidirgan</th>
            <th>Session</th>
            <th>Draft</th>
          </tr>
        </thead>
        <tbody>
          @forelse($histories as $item)
            <tr>
              <td style="white-space:nowrap;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-hint)">
                {{ $item->updated_at?->format('d.m.Y H:i:s') }}
              </td>
              <td>
                <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $item->text }}</div>
              </td>
              <td>
                <div style="font-size:13px;color:var(--p-text)">{{ $item->result_name ?: '—' }}</div>
                @if($item->result_count)
                  <div style="font-size:11px;color:var(--p-hint)">Natijalar: {{ $item->result_count }}</div>
                @endif
              </td>
              <td>
                <span class="s-pill accent">{{ $item->result_type ?: '—' }}</span>
              </td>
              <td>
                @if($item->user)
                  <div style="font-size:13px;font-weight:600;color:var(--p-text)">
                    {{ trim(($item->user->name ?? '') . ' ' . ($item->user->lastname ?? '')) ?: 'User #' . $item->user->id }}
                  </div>
                  <div style="font-size:11px;color:var(--p-hint)">
                    @if($item->user->username) @{{ $item->user->username }} · @endif
                    {{ $item->user->phone ?: 'ID: ' . $item->user->id }}
                  </div>
                @else
                  <span class="s-pill warning">Guest</span>
                @endif
              </td>
              <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $item->session_id }}">
                <code style="font-size:12px">{{ $item->session_id ?: '—' }}</code>
              </td>
              <td>
                <span class="s-pill {{ $item->is_draft ? 'warning' : 'success' }}">
                  {{ $item->is_draft ? 'Ha' : 'Yo‘q' }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center;padding:36px;color:var(--p-hint)">Qidiruv tarixi topilmadi</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  @if($histories->hasPages())
    <div>{{ $histories->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection
