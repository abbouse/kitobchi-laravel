@extends('a122.layouts.admin')
@section('title', 'Jo‘natmani tahrirlash')
@section('page-title', 'Jo‘natmani tahrirlash')

@section('content')
<div class="max-w-5xl space-y-6">
  <x-a122.page-header back-href="{{ route('admin.bloggers.show', $blogger) }}">
    <x-slot name="heading">#BLG-{{ $shipment->id }} jo‘natmasi</x-slot>
    <x-slot name="meta">{{ $blogger->full_name }} · {{ $shipment->status_label }}</x-slot>
  </x-a122.page-header>

  <div class="card-panel p-6">
    <form method="POST" action="{{ route('admin.bloggers.shipments.update', [$blogger, $shipment]) }}" class="space-y-4">
      @csrf
      @method('PUT')

      @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 text-sm">
          <ul class="space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Jo‘natma vaqti</label>
          <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for', $shipment->scheduled_for?->format('Y-m-d\TH:i')) }}" class="input" required>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
          <select name="status" class="input">
            <option value="pending" @selected(old('status', $shipment->status) === 'pending')>Yetkazilmadi</option>
            <option value="delivered" @selected(old('status', $shipment->status) === 'delivered')>Yetkazildi</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Izoh</label>
          <input name="note" value="{{ old('note', $shipment->note) }}" class="input">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Itemlar ro‘yxati</label>
        <textarea name="items_text" rows="10" class="input" required>{{ old('items_text', $shipment->items->pluck('name')->implode("\n")) }}</textarea>
      </div>

      <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-white/5">
        <button type="submit" class="btn btn-primary">Saqlash</button>
        <a href="{{ route('admin.bloggers.show', $blogger) }}" class="btn btn-secondary">Orqaga</a>
      </div>
    </form>
  </div>
</div>
@endsection
