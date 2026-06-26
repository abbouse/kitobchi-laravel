@php
    $lang = ($lang ?? 'uz') === 'ru' ? 'ru' : 'uz';
    $fmt = fn ($value) => number_format((int) $value, 0, '.', ' ');
    $rows = collect($rows ?? []);
    $isCourier = $owner === 'courier';

    $labels = [
        'uz' => [
            'brand' => 'Kitobchi',
            'title' => 'Pul yechish bo‘yicha hisob-kitob hujjati',
            'subtitle' => 'Yakunlangan buyurtmalar asosida shakllantirilgan hisobot',
            'owner_seller' => 'Sotuvchi',
            'owner_courier' => 'Kuryer',
            'document_no' => 'Hujjat raqami',
            'generated_at' => 'Yaratilgan sana',
            'recipient' => 'Qabul qiluvchi',
            'phone_missing' => 'Telefon ko‘rsatilmagan',
            'payment_method' => 'To‘lov rekviziti',
            'transaction' => 'Tranzaksiya',
            'status' => 'Holat',
            'period' => 'Hisobot davri',
            'from_start' => 'Boshlanishidan',
            'request_amount' => 'Ariza summasi',
            'request_date' => 'Ariza sanasi',
            'orders' => 'Buyurtmalar',
            'products' => 'Mahsulotlar',
            'commission' => 'Komissiya',
            'bonus' => 'Bonus',
            'final_amount' => 'Yechib beriladigan summa',
            'orders_title' => 'Hisob-kitob tarkibidagi buyurtmalar',
            'main_order' => 'Asosiy buyurtma',
            'sub_seller' => 'Sotuvchi buyurtmasi',
            'sub_courier' => 'Kuryer buyurtmasi',
            'transaction_short' => 'Tranzaksiya',
            'quantity' => 'Soni',
            'gross' => 'Umumiy summa',
            'base_payout' => 'Asosiy haq',
            'final' => 'Yakuniy',
            'empty' => 'Ushbu tranzaksiya uchun buyurtmalar topilmadi.',
            'summary' => 'Yakuniy hisob-kitob',
            'note' => 'Ushbu hujjat Kitobchi tizimida yakunlangan va tasdiqlangan buyurtmalar asosida avtomatik shakllantirildi.',
            'kitobchi_rep' => 'Kitobchi vakili',
            'owner_rep' => 'vakili',
            'stamp' => 'Imzo / muhr',
            'signature' => 'Imzo',
        ],
        'ru' => [
            'brand' => 'Kitobchi',
            'title' => 'Расчетный документ на вывод средств',
            'subtitle' => 'Отчет сформирован на основании завершенных заказов',
            'owner_seller' => 'Продавец',
            'owner_courier' => 'Курьер',
            'document_no' => 'Номер документа',
            'generated_at' => 'Дата формирования',
            'recipient' => 'Получатель',
            'phone_missing' => 'Телефон не указан',
            'payment_method' => 'Платежные реквизиты',
            'transaction' => 'Транзакция',
            'status' => 'Статус',
            'period' => 'Период отчета',
            'from_start' => 'С начала',
            'request_amount' => 'Сумма заявки',
            'request_date' => 'Дата заявки',
            'orders' => 'Заказы',
            'products' => 'Товары',
            'commission' => 'Комиссия',
            'bonus' => 'Бонус',
            'final_amount' => 'Сумма к выплате',
            'orders_title' => 'Заказы в составе расчета',
            'main_order' => 'Основной заказ',
            'sub_seller' => 'Заказ продавца',
            'sub_courier' => 'Заказ курьера',
            'transaction_short' => 'Транзакция',
            'quantity' => 'Кол-во',
            'gross' => 'Общая сумма',
            'base_payout' => 'Основная сумма',
            'final' => 'Итого',
            'empty' => 'По этой транзакции заказы не найдены.',
            'summary' => 'Итоговый расчет',
            'note' => 'Документ автоматически сформирован на основании завершенных и подтвержденных заказов в системе Kitobchi.',
            'kitobchi_rep' => 'Представитель Kitobchi',
            'owner_rep' => 'представитель',
            'stamp' => 'Подпись / печать',
            'signature' => 'Подпись',
        ],
    ];

    $t = $labels[$lang];
    $ownerLabel = $isCourier ? $t['owner_courier'] : $t['owner_seller'];
    $periodFrom = ($period['from'] ?? null) ?: $t['from_start'];
    $periodTo = $period['to'] ?? ($transaction['created_at'] ?? '—');
    $currency = $lang === 'ru' ? 'сум' : 'so‘m';
@endphp
<!doctype html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.45; }
        .top { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 16px; }
        .top td { vertical-align: top; }
        .brand { font-size: 23px; font-weight: 800; letter-spacing: .2px; }
        .doc-title { font-size: 18px; font-weight: 800; margin-top: 9px; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .meta-box { display: inline-block; text-align: left; border: 1px solid #e5e7eb; border-radius: 8px; padding: 9px 11px; background: #f9fafb; }
        .section-title { font-size: 12px; font-weight: 800; margin: 18px 0 8px; }
        .label { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: .35px; }
        .value { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .info-grid { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .info-grid td { width: 50%; vertical-align: top; padding: 10px 12px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .info-grid tr:last-child td { border-bottom: 0; }
        .info-grid td:last-child { border-right: 0; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 12px -8px 14px; }
        .cards td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 9px; background: #f9fafb; }
        .card-value { font-size: 15px; font-weight: 800; margin-top: 2px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; border: 1px solid #e5e7eb; }
        .lines th { background: #111827; color: white; padding: 8px 6px; font-size: 9px; text-align: left; }
        .lines td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; vertical-align: top; }
        .lines tr:last-child td { border-bottom: 0; }
        .summary-wrap { width: 100%; margin-top: 16px; }
        .summary { width: 46%; margin-left: auto; border-collapse: collapse; border: 1px solid #e5e7eb; }
        .summary td { padding: 8px 9px; border-bottom: 1px solid #e5e7eb; }
        .summary tr:last-child td { font-weight: 800; font-size: 13px; background: #f9fafb; border-bottom: 0; }
        .note { margin-top: 16px; padding: 11px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 9px; color: #374151; }
        .signatures { width: 100%; margin-top: 31px; border-collapse: collapse; }
        .signatures td { width: 50%; padding-right: 28px; vertical-align: bottom; }
        .line { border-bottom: 1px solid #111827; height: 34px; margin-bottom: 5px; }
        .footer { position: fixed; bottom: -16px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ $t['brand'] }}</div>
                <div class="doc-title">{{ $t['title'] }}</div>
                <div class="muted">{{ $t['subtitle'] }}</div>
            </td>
            <td class="right">
                <div class="meta-box">
                    <div class="label">{{ $t['document_no'] }}</div>
                    <div class="value">{{ $document_no }}</div>
                    <div class="label" style="margin-top: 6px;">{{ $t['generated_at'] }}</div>
                    <div>{{ $generated_at }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ $t['recipient'] }}</div>
    <table class="info-grid">
        <tr>
            <td>
                <div class="label">{{ $ownerLabel }}</div>
                <div class="value">{{ $recipient['name'] }}</div>
                <div class="muted">{{ $recipient['phone'] ?: $t['phone_missing'] }}</div>
            </td>
            <td>
                <div class="label">{{ $t['payment_method'] }}</div>
                <div class="value">{{ $transaction['method'] }}</div>
                <div class="muted">{{ $t['transaction'] }} #{{ $transaction['id'] }} · {{ $t['status'] }}: {{ $transaction['status'] }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $t['period'] }}</div>
                <div class="value">{{ $periodFrom }} - {{ $periodTo }}</div>
            </td>
            <td>
                <div class="label">{{ $t['request_amount'] }}</div>
                <div class="value">{{ $fmt($transaction['net']) }} {{ $currency }}</div>
                <div class="muted">{{ $t['request_date'] }}: {{ $transaction['created_at'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ $t['summary'] }}</div>
    <table class="cards">
        <tr>
            <td><div class="label">{{ $t['orders'] }}</div><div class="card-value">{{ $totals['orders'] }}</div></td>
            <td><div class="label">{{ $t['products'] }}</div><div class="card-value">{{ $totals['products'] }}</div></td>
            <td><div class="label">{{ $isCourier ? $t['bonus'] : $t['commission'] }}</div><div class="card-value">{{ $fmt($isCourier ? $totals['bonus'] : $totals['commission']) }}</div></td>
            <td><div class="label">{{ $t['final_amount'] }}</div><div class="card-value">{{ $fmt($totals['net']) }}</div></td>
        </tr>
    </table>

    <div class="section-title">{{ $t['orders_title'] }}</div>
    <table class="lines">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $t['main_order'] }}</th>
                <th>{{ $isCourier ? $t['sub_courier'] : $t['sub_seller'] }}</th>
                <th class="right">{{ $t['quantity'] }}</th>
                <th class="right">{{ $isCourier ? $t['base_payout'] : $t['gross'] }}</th>
                <th class="right">{{ $isCourier ? $t['bonus'] : $t['commission'] }}</th>
                <th class="right">{{ $t['final'] }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>#{{ $row['order_id'] ?: '—' }}<br><span class="muted">{{ $row['date'] }}</span></td>
                <td>#{{ $row['sub_order_id'] ?: '—' }}<br><span class="muted">{{ $t['transaction_short'] }} #{{ $row['transaction_id'] }}</span></td>
                <td class="right">{{ $row['product_count'] }}</td>
                <td class="right">{{ $fmt($isCourier ? ($row['base_payout'] ?? $row['gross']) : $row['gross']) }}</td>
                <td class="right">{{ $fmt($isCourier ? ($row['bonus'] ?? 0) : $row['commission']) }}</td>
                <td class="right"><strong>{{ $fmt($row['net']) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">{{ $t['empty'] }}</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary">
            <tr><td>{{ $t['gross'] }}</td><td class="right">{{ $fmt($totals['gross']) }} {{ $currency }}</td></tr>
            @if($isCourier)
                <tr><td>{{ $t['base_payout'] }}</td><td class="right">{{ $fmt($totals['base_payout']) }} {{ $currency }}</td></tr>
                <tr><td>{{ $t['bonus'] }}</td><td class="right">{{ $fmt($totals['bonus']) }} {{ $currency }}</td></tr>
            @else
                <tr><td>{{ $t['commission'] }}</td><td class="right">{{ $fmt($totals['commission']) }} {{ $currency }}</td></tr>
            @endif
            <tr><td>{{ $t['final_amount'] }}</td><td class="right">{{ $fmt($totals['net']) }} {{ $currency }}</td></tr>
        </table>
    </div>

    <div class="note">{{ $t['note'] }}</div>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <strong>{{ $t['kitobchi_rep'] }}</strong><br>
                <span class="muted">{{ $t['stamp'] }}</span>
            </td>
            <td>
                <div class="line"></div>
                <strong>{{ $ownerLabel }} {{ $t['owner_rep'] }}</strong><br>
                <span class="muted">{{ $t['signature'] }}</span>
            </td>
        </tr>
    </table>

    <div class="footer">Kitobchi · {{ $document_no }}</div>
</body>
</html>
