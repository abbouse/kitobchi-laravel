@extends('a122.layouts.guest')
@section('title', 'Hub Desk')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-6">
  <div class="mx-auto max-w-4xl space-y-5">
    <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <a href="{{ route('hubdesk.index') }}" class="text-sm font-medium text-sky-700 hover:underline">← Orqaga</a>
          <div class="mt-2 text-2xl font-bold text-slate-900">#ORD-{{ $fulfillment->order_id }}</div>
          <div class="mt-1 text-sm text-slate-500">{{ $fulfillment->hub?->name }} · {{ $fulfillment->status_code }}</div>
        </div>
        <div class="flex flex-wrap gap-2">
          @if(in_array('print.label', $permissions, true))
            <a href="{{ route('hubdesk.print.label', $fulfillment) }}" target="_blank" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Label print</a>
          @endif
          @if(in_array('print.receipt', $permissions, true))
            <a href="{{ route('hubdesk.print.receipt', $fulfillment) }}" target="_blank" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Receipt print</a>
          @endif
        </div>
      </div>

      @php
        $exception = data_get($fulfillment->meta, 'exception');
      @endphp

      <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-2xl bg-slate-50 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-500">Label / Tracking</div>
          <div class="mt-2 font-semibold text-slate-900">{{ $fulfillment->label_code ?: 'Label yo‘q' }}</div>
          <div class="mt-1 text-slate-500">{{ $fulfillment->postal_tracking_number ?: 'Tracking yo‘q' }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-500">COD</div>
          <div class="mt-2 font-semibold text-slate-900">
            @if($fulfillment->is_cod)
              {{ number_format((int) $fulfillment->cash_collect_amount, 0, '.', ' ') }} UZS
            @else
              Yo‘q
            @endif
          </div>
        </div>
      </div>

      @if(!empty($exception))
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          <div class="font-semibold">Faol exception: {{ data_get($exception, 'code', 'other') }}</div>
          <div class="mt-1">{{ data_get($exception, 'note') ?: 'Izoh yo‘q' }}</div>
          @if(data_get($exception, 'resolved'))
            <div class="mt-2 text-xs text-amber-700">Yopilgan</div>
          @endif
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
