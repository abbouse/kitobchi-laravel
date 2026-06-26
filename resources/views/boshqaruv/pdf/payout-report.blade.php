@php
    $fmt = fn ($value) => number_format((int) $value, 0, '.', ' ');
    $ownerLabel = $owner === 'courier' ? 'Kuryer' : 'Seller';
    $rows = collect($rows ?? []);
@endphp
<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.45; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 22px; font-weight: 800; letter-spacing: .3px; }
        .muted { color: #6b7280; }
        .title { font-size: 18px; font-weight: 800; margin: 12px 0 4px; }
        .doc-no { font-size: 12px; color: #374151; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .grid td { width: 50%; vertical-align: top; padding: 7px 9px; border: 1px solid #e5e7eb; }
        .label { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: .4px; }
        .value { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .cards { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .cards td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb; }
        .card-value { font-size: 16px; font-weight: 800; margin-top: 2px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .lines th { background: #111827; color: white; padding: 8px 6px; font-size: 9px; text-align: left; }
        .lines td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; vertical-align: top; }
        .right { text-align: right; }
        .summary { margin-top: 16px; width: 45%; margin-left: auto; border-collapse: collapse; }
        .summary td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        .summary tr:last-child td { font-weight: 800; font-size: 13px; border-top: 2px solid #111827; border-bottom: 0; }
        .note { margin-top: 18px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; color: #374151; }
        .signatures { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .signatures td { width: 50%; padding-right: 24px; vertical-align: bottom; }
        .line { border-bottom: 1px solid #111827; height: 36px; margin-bottom: 5px; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Kitobchi Marketplace</div>
        <div class="muted">To'lov hisoboti va yechib olish uchun asos hujjati</div>
        <div class="title">{{ $ownerLabel }} payout hisoboti</div>
        <div class="doc-no">Hujjat raqami: {{ $document_no }} · Yaratildi: {{ $generated_at }}</div>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ $ownerLabel }}</div>
                <div class="value">{{ $recipient['name'] }}</div>
                <div class="muted">{{ $recipient['phone'] ?: 'Telefon ko‘rsatilmagan' }}</div>
            </td>
            <td>
                <div class="label">To'lov rekviziti</div>
                <div class="value">{{ $transaction['method'] }}</div>
                <div class="muted">Tranzaksiya #{{ $transaction['id'] }} · {{ $transaction['status'] }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Hisobot davri</div>
                <div class="value">{{ $period['from'] }} - {{ $period['to'] }}</div>
            </td>
            <td>
                <div class="label">Ariza summasi</div>
                <div class="value">{{ $fmt($transaction['net']) }} so'm</div>
                <div class="muted">Ariza sanasi: {{ $transaction['created_at'] }}</div>
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="label">Orderlar</div><div class="card-value">{{ $totals['orders'] }}</div></td>
            <td><div class="label">Mahsulotlar</div><div class="card-value">{{ $totals['products'] }}</div></td>
            <td><div class="label">{{ $owner === 'courier' ? 'Bonus' : 'Komissiya' }}</div><div class="card-value">{{ $fmt($owner === 'courier' ? $totals['bonus'] : $totals['commission']) }}</div></td>
            <td><div class="label">Yakuniy payout</div><div class="card-value">{{ $fmt($totals['net']) }}</div></td>
        </tr>
    </table>

    <div class="label">Payout tarkibidagi orderlar</div>
    <table class="lines">
        <thead>
            <tr>
                <th>#</th>
                <th>Order</th>
                <th>{{ $owner === 'courier' ? 'Courier order' : 'Seller order' }}</th>
                <th class="right">Mahsulot</th>
                <th class="right">{{ $owner === 'courier' ? 'Bazaviy' : 'Brutto' }}</th>
                <th class="right">{{ $owner === 'courier' ? 'Bonus' : 'Komissiya' }}</th>
                <th class="right">Net</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>#{{ $row['order_id'] ?: '—' }}<br><span class="muted">{{ $row['date'] }}</span></td>
                <td>#{{ $row['sub_order_id'] ?: '—' }}<br><span class="muted">TRX #{{ $row['transaction_id'] }}</span></td>
                <td class="right">{{ $row['product_count'] }}</td>
                <td class="right">{{ $fmt($owner === 'courier' ? ($row['base_payout'] ?? $row['gross']) : $row['gross']) }}</td>
                <td class="right">{{ $fmt($owner === 'courier' ? ($row['bonus'] ?? 0) : $row['commission']) }}</td>
                <td class="right"><strong>{{ $fmt($row['net']) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Ushbu tranzaksiya uchun order breakdown topilmadi.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Brutto</td><td class="right">{{ $fmt($totals['gross']) }} so'm</td></tr>
        @if($owner === 'courier')
            <tr><td>Bazaviy payout</td><td class="right">{{ $fmt($totals['base_payout']) }} so'm</td></tr>
            <tr><td>Bonus</td><td class="right">{{ $fmt($totals['bonus']) }} so'm</td></tr>
        @else
            <tr><td>Komissiya</td><td class="right">{{ $fmt($totals['commission']) }} so'm</td></tr>
        @endif
        <tr><td>Yechib beriladigan summa</td><td class="right">{{ $fmt($totals['net']) }} so'm</td></tr>
    </table>

    <div class="note">
        Ushbu hujjat Kitobchi platformasida yakunlangan va tasdiqlangan orderlar asosida avtomatik shakllantirildi.
        Admin tranzaksiyani tasdiqlashidan oldin Didox orqali yuborish uchun hujjat sifatida foydalanishi mumkin.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <strong>Kitobchi vakili</strong><br>
                <span class="muted">Imzo / muhr</span>
            </td>
            <td>
                <div class="line"></div>
                <strong>{{ $ownerLabel }} vakili</strong><br>
                <span class="muted">Imzo</span>
            </td>
        </tr>
    </table>

    <div class="footer">Kitobchi Marketplace · {{ $document_no }}</div>
</body>
</html>
