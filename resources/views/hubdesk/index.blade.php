@extends('a122.layouts.guest')
@section('title', 'Hub Desk')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-6">
  <div class="mx-auto max-w-6xl space-y-5">
    <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Hub Desk</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ $staff->hub?->name ?: 'Hub' }}</div>
          <div class="mt-1 text-sm text-slate-500">{{ $staff->full_name }} · {{ $staff->role }}</div>
        </div>
        <form method="POST" action="{{ route('hubdesk.logout') }}">
          @csrf
          <button class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Chiqish</button>
        </form>
      </div>

      <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-2xl bg-slate-50 px-4 py-3">
          <div class="text-xs uppercase tracking-wide text-slate-500">Label tayyor</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($counts['ready_to_label'] ?? 0) }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 px-4 py-3">
          <div class="text-xs uppercase tracking-wide text-slate-500">Dispatch tayyor</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($counts['ready_to_dispatch'] ?? 0) }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 px-4 py-3">
          <div class="text-xs uppercase tracking-wide text-slate-500">Exceptionlar</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($counts['with_exceptions'] ?? 0) }}</div>
        </div>
      </div>
    </div>

    <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
      <form method="GET" class="flex flex-col gap-3 md:flex-row">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Order ID, label, tracking, mijoz yoki telefon" class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-sky-400">
        <button class="rounded-2xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-700">Qidirish</button>
      </form>
    </div>

    <div class="rounded-[28px] border border-slate-200 bg-white p-3 shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-3 py-3">Order</th>
              <th class="px-3 py-3">Mijoz</th>
              <th class="px-3 py-3">Status</th>
              <th class="px-3 py-3">Label</th>
              <th class="px-3 py-3">Print</th>
            </tr>
          </thead>
          <tbody>
            @forelse($fulfillments as $fulfillment)
              @php
                $order = $fulfillment->order;
                $customer = $order?->user?->full_name ?: 'Mijoz';
              @endphp
              <tr class="border-t border-slate-100">
                <td class="px-3 py-3">
                  <a href="{{ route('hubdesk.show', $fulfillment) }}" class="font-semibold text-sky-700 hover:underline">#ORD-{{ $fulfillment->order_id }}</a>
                  <div class="mt-1 text-xs text-slate-500">{{ $fulfillment->hub?->code }}</div>
                </td>
                <td class="px-3 py-3">
                  <div class="font-medium text-slate-900">{{ $customer }}</div>
                  <div class="mt-1 text-xs text-slate-500">{{ $order?->user?->phone_number ?: 'Telefon yo‘q' }}</div>
                </td>
                <td class="px-3 py-3">
                  <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $fulfillment->status_code }}</span>
                </td>
                <td class="px-3 py-3 text-xs text-slate-500">{{ $fulfillment->label_code ?: 'Label yo‘q' }}</td>
                <td class="px-3 py-3">
                  <div class="flex flex-wrap gap-2">
                    @if(in_array('print.label', $permissions, true))
                      <a href="{{ route('hubdesk.print.label', $fulfillment) }}" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Label</a>
                    @endif
                    @if(in_array('print.receipt', $permissions, true))
                      <a href="{{ route('hubdesk.print.receipt', $fulfillment) }}" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Receipt</a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-500">Fulfillment topilmadi.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-4">
        {{ $fulfillments->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
