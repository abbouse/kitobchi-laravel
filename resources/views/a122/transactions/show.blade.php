@extends('a122.layouts.admin')
@section('title', 'Tranzaksiya #' . $transaction->id)
@section('page-title', 'Tranzaksiya tafsilotlari')
@section('page-eyebrow', 'Payout operations')

@section('content')
@php
    $statusTone = $transaction->status === 'approved'
        ? 'text-bg-success-subtle border border-success-subtle text-success-emphasis'
        : ($transaction->status === 'rejected'
            ? 'text-bg-danger-subtle border border-danger-subtle text-danger-emphasis'
            : 'text-bg-warning-subtle border border-warning-subtle text-warning-emphasis');
    $margin = (float) ($transaction->commissionPrice ?? 0);
    $gross = (float) ($transaction->amount ?? 0);
    $net = (float) ($transaction->netAmount ?? 0);
@endphp

<div class="d-flex flex-column gap-4">
    <x-admin.page-header
        eyebrow="Payout operations"
        :title="'#TRX-' . $transaction->id"
        :subtitle="($transaction->seller?->shop_name ?: 'Seller yo‘q') . ' · ' . optional($transaction->created_at)->format('d.m.Y H:i')">
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Ro‘yxatga qaytish
        </a>
    </x-admin.page-header>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Brutto" :value="number_format($gross, 0, '.', ' ') . ' UZS'" meta="Umumiy payout summasi" icon="wallet2" tone="dark" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Komissiya" :value="number_format($margin, 0, '.', ' ') . ' UZS'" :meta="($transaction->commissionPercent ?: 0) . '%'" icon="percent" tone="warning" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Sof summa" :value="number_format($net, 0, '.', ' ') . ' UZS'" meta="Sellerga tushadigan qism" icon="cash-coin" tone="success" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-admin.stat-card label="Holat" :value="$transaction->status_label" meta="Joriy payout bosqichi" icon="diagram-3" tone="primary" /></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <x-admin.section-card title="Tranzaksiya ma’lumotlari" meta="Payout yozuvi, seller konteksti va hisob-kitob tarkibi.">
                <div class="row g-4 small">
                    <div class="col-sm-6"><div class="text-secondary mb-1">Tranzaksiya ID</div><div class="fw-semibold">#{{ $transaction->id }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sana</div><div>{{ $transaction->created_at ? $transaction->created_at->format('d.m.Y H:i') : '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sotuvchi</div><div>@if($transaction->seller)<a href="{{ route('admin.sellers.show', $transaction->seller) }}" class="link-success text-decoration-none fw-semibold">{{ $transaction->seller->shop_name }}</a>@else—@endif</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Telefon</div><div>{{ $transaction->seller?->phone_number ?: '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Brutto miqdor</div><div>{{ number_format((float)($transaction->amount ?? 0), 0, '.', ' ') }} UZS</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Holat</div><div><span class="badge rounded-pill {{ $statusTone }}">{{ $transaction->status_label }}</span></div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Komissiya %</div><div>{{ $transaction->commissionPercent ?: '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Komissiya summasi</div><div>{{ $transaction->commissionPrice ? number_format((float)$transaction->commissionPrice, 0, '.', ' ') . ' UZS' : '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Sof summa</div><div>{{ $transaction->netAmount ? number_format((float)$transaction->netAmount, 0, '.', ' ') . ' UZS' : '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Karta</div><div>{{ $transaction->card ?: '—' }}</div></div>
                    <div class="col-sm-6"><div class="text-secondary mb-1">Yangilangan</div><div>{{ optional($transaction->updated_at)->format('d.m.Y H:i') ?: '—' }}</div></div>
                </div>

                @if($transaction->comment ?? $transaction->note ?? $transaction->rejected_desc)
                    <div class="mt-4 pt-4 border-top">
                        <div class="fw-semibold mb-2">Izoh</div>
                        <div class="text-secondary">{{ $transaction->comment ?? $transaction->note ?? $transaction->rejected_desc }}</div>
                    </div>
                @endif
            </x-admin.section-card>
        </div>

        <div class="col-12 col-xl-4">
            <x-admin.section-card title="Seller summary" meta="Shu seller bo‘yicha payout oqimi va operatsion actionlar.">
                <div class="d-grid gap-3">
                    <x-admin.stat-card label="Tasdiqlangan tranzaksiyalar" :value="number_format((int) $sellerTotals['approved_count'])" icon="check2-circle" tone="success" />
                    <x-admin.stat-card label="Tasdiqlangan summa" :value="number_format((float) $sellerTotals['approved_sum'], 0, '.', ' ') . ' UZS'" icon="bank" tone="info" />
                    <x-admin.stat-card label="Pending summa" :value="number_format((float) $sellerTotals['pending_sum'], 0, '.', ' ') . ' UZS'" icon="hourglass-split" tone="warning" />
                </div>

                @if($transaction->status === 'pending')
                    <div class="d-grid gap-3 mt-4">
                        <form method="POST" action="{{ route('admin.transactions.approve', $transaction) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-check2-circle me-2"></i>Tasdiqlash
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.transactions.reject', $transaction) }}" onsubmit="return confirm('Tranzaksiyani rad etishga ishonchingiz komilmi?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger w-100 rounded-pill">
                                <i class="bi bi-x-circle me-2"></i>Rad etish
                            </button>
                        </form>
                    </div>
                @endif
            </x-admin.section-card>
        </div>
    </div>

    <x-admin.section-card title="Sellerning yaqin tranzaksiyalari" :meta="$sellerTransactions->count() . ' ta yozuv'" >
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Miqdor</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="text-end">Ko‘rish</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sellerTransactions as $row)
                        <tr>
                            <td>#TRX-{{ $row->id }}</td>
                            <td class="font-monospace">{{ number_format((float) $row->amount, 0, '.', ' ') }} UZS</td>
                            <td><span class="badge rounded-pill text-bg-light border">{{ $row->status_label }}</span></td>
                            <td>{{ optional($row->created_at)->format('d.m.Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.transactions.show', $row) }}" class="btn btn-sm btn-dark rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i>Ko‘rish
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">Boshqa tranzaksiyalar topilmadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section-card>
</div>
@endsection
