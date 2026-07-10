@php
    $lang = ($lang ?? 'uz') === 'ru' ? 'ru' : 'uz';
    $fmt = fn ($value) => number_format((int) $value, 0, '.', ' ');
    $rows = collect($rows ?? []);
    $isCourier = $owner === 'courier';

    $labels = [
        'uz' => [
            'brand' => 'Kitobchi',
            'title' => 'HISOB-KITOB HUJJATI',
            'subtitle' => 'Pul mablag‘larini yechib berish bo‘yicha',
            'owner_seller' => 'Sotuvchi',
            'owner_courier' => 'Kuryer',
            'document_no' => 'Hujjat №',
            'generated_at' => 'Sana',
            'recipient' => 'QABUL QILUVCHI',
            'payer' => 'TO‘LOVCHI',
            'phone_missing' => 'Telefon ko‘rsatilmagan',
            'payment_method' => 'To‘lov rekviziti',
            'transaction' => 'Tranzaksiya',
            'status' => 'Holat',
            'period' => 'Hisobot davri',
            'from_start' => 'Boshlanishidan',
            'request_amount' => 'Ariza summasi',
            'request_date' => 'Ariza sanasi',
            'orders' => 'Buyurtmalar soni',
            'products' => 'Mahsulotlar soni',
            'commission' => 'Komissiya',
            'bonus' => 'Bonus',
            'final_amount' => 'YECHIB BERILADIGAN SUMMA',
            'orders_title' => 'Hisob-kitob tarkibidagi buyurtmalar',
            'main_order' => 'Buyurtma',
            'sub_seller' => 'Sotuvchi buyurtmasi',
            'sub_courier' => 'Kuryer buyurtmasi',
            'date' => 'Sana',
            'quantity' => 'Soni',
            'gross' => 'Umumiy summa',
            'base_payout' => 'Asosiy haq',
            'final' => 'Yakuniy',
            'empty' => 'Ushbu tranzaksiya uchun buyurtmalar topilmadi.',
            'summary' => 'Yakuniy hisob-kitob',
            'note' => 'Ushbu hujjat Kitobchi elektron tizimida yakunlangan va tasdiqlangan buyurtmalar asosida avtomatik shakllantirilgan bo‘lib, tomonlar o‘rtasidagi o‘zaro hisob-kitobni tasdiqlovchi hujjat hisoblanadi.',
            'kitobchi_rep' => 'Kitobchi MChJ vakili',
            'owner_rep' => 'vakili',
            'stamp' => 'M.O‘.',
            'signature' => '(imzo)',
            'fio' => '(F.I.Sh.)',
        ],
        'ru' => [
            'brand' => 'Kitobchi',
            'title' => 'РАСЧЕТНЫЙ ДОКУМЕНТ',
            'subtitle' => 'на вывод денежных средств',
            'owner_seller' => 'Продавец',
            'owner_courier' => 'Курьер',
            'document_no' => 'Документ №',
            'generated_at' => 'Дата',
            'recipient' => 'ПОЛУЧАТЕЛЬ',
            'payer' => 'ПЛАТЕЛЬЩИК',
            'phone_missing' => 'Телефон не указан',
            'payment_method' => 'Платежные реквизиты',
            'transaction' => 'Транзакция',
            'status' => 'Статус',
            'period' => 'Период отчета',
            'from_start' => 'С начала',
            'request_amount' => 'Сумма заявки',
            'request_date' => 'Дата заявки',
            'orders' => 'Количество заказов',
            'products' => 'Количество товаров',
            'commission' => 'Комиссия',
            'bonus' => 'Бонус',
            'final_amount' => 'СУММА К ВЫПЛАТЕ',
            'orders_title' => 'Заказы в составе расчета',
            'main_order' => 'Заказ',
            'sub_seller' => 'Заказ продавца',
            'sub_courier' => 'Заказ курьера',
            'date' => 'Дата',
            'quantity' => 'Кол-во',
            'gross' => 'Общая сумма',
            'base_payout' => 'Основная сумма',
            'final' => 'Итого',
            'empty' => 'По этой транзакции заказы не найдены.',
            'summary' => 'Итоговый расчет',
            'note' => 'Настоящий документ сформирован автоматически на основании завершенных и подтвержденных заказов в электронной системе Kitobchi и является документом, подтверждающим взаиморасчеты между сторонами.',
            'kitobchi_rep' => 'Представитель ООО Kitobchi',
            'owner_rep' => 'представитель',
            'stamp' => 'М.П.',
            'signature' => '(подпись)',
            'fio' => '(Ф.И.О.)',
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
        @page { margin: 34px 40px 44px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10.5px; line-height: 1.4; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #444; }
        .small { font-size: 9px; }

        /* Sarlavha — rasmiy, markazda */
        .doc-head { text-align: center; margin-bottom: 4px; }
        .doc-title { font-size: 15px; font-weight: 700; letter-spacing: 1px; }
        .doc-subtitle { font-size: 10.5px; margin-top: 1px; }
        .doc-meta { text-align: center; margin: 6px 0 18px; font-size: 10.5px; }
        .head-rule { border-bottom: 1.4px solid #000; margin: 12px 0 14px; }

        /* Rekvizitlar */
        table.req { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.req td { width: 50%; vertical-align: top; padding: 0 14px 0 0; }
        table.req td + td { padding: 0 0 0 14px; }
        .req-title { font-size: 9px; font-weight: 700; letter-spacing: .6px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 6px; }
        .req-row { margin-bottom: 3px; }
        .req-label { color: #444; }

        /* Jadval */
        table.lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .lines th { border: 1px solid #000; background: #f2f2f2; padding: 6px 5px; font-size: 9px; font-weight: 700; text-align: left; }
        .lines td { border: 1px solid #000; padding: 5px; vertical-align: top; }

        .section-title { font-size: 11px; font-weight: 700; margin: 14px 0 4px; }

        /* Yakuniy hisob-kitob */
        table.summary { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 12px; }
        .summary td { border: 1px solid #000; padding: 6px 8px; }
        .summary tr.total td { font-weight: 700; font-size: 11.5px; background: #f2f2f2; }

        .note { margin-top: 14px; font-size: 9px; color: #333; text-align: justify; }

        /* Imzolar */
        table.signatures { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .signatures td { width: 50%; vertical-align: top; padding-right: 30px; }
        .sig-role { font-weight: 700; margin-bottom: 26px; }
        .sig-line { border-bottom: 1px solid #000; height: 1px; margin-bottom: 3px; }
        .sig-caption { font-size: 8.5px; color: #444; }
        table.sig-inner { width: 100%; border-collapse: collapse; }
        table.sig-inner td { padding: 0 6px 0 0; border: 0; width: 55%; }
        table.sig-inner td + td { width: 45%; padding: 0 0 0 6px; }

        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; color: #666; font-size: 8.5px; border-top: .5px solid #999; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="doc-head">
        <div class="doc-title">{{ $t['title'] }}</div>
        <div class="doc-subtitle">{{ $t['subtitle'] }}</div>
    </div>
    <div class="doc-meta">
        {{ $t['document_no'] }} <strong>{{ $document_no }}</strong>
        &nbsp;·&nbsp; {{ $t['generated_at'] }}: <strong>{{ $generated_at }}</strong>
    </div>
    <div class="head-rule"></div>

    <table class="req">
        <tr>
            <td>
                <div class="req-title">{{ $t['payer'] }}</div>
                <div class="req-row"><strong>{{ $t['brand'] }}</strong></div>
                <div class="req-row muted">{{ $t['transaction'] }} №{{ $transaction['id'] }} · {{ $t['status'] }}: {{ $transaction['status'] }}</div>
                <div class="req-row"><span class="req-label">{{ $t['period'] }}:</span> {{ $periodFrom }} — {{ $periodTo }}</div>
            </td>
            <td>
                <div class="req-title">{{ $t['recipient'] }} ({{ $ownerLabel }})</div>
                <div class="req-row"><strong>{{ $recipient['name'] }}</strong></div>
                <div class="req-row muted">{{ $recipient['phone'] ?: $t['phone_missing'] }}</div>
                <div class="req-row"><span class="req-label">{{ $t['payment_method'] }}:</span> {{ $transaction['method'] }}</div>
                <div class="req-row"><span class="req-label">{{ $t['request_date'] }}:</span> {{ $transaction['created_at'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ $t['orders_title'] }}</div>
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 4%;">№</th>
                <th>{{ $t['main_order'] }}</th>
                <th>{{ $isCourier ? $t['sub_courier'] : $t['sub_seller'] }}</th>
                <th style="width: 8%;" class="right">{{ $t['quantity'] }}</th>
                <th style="width: 15%;" class="right">{{ $isCourier ? $t['base_payout'] : $t['gross'] }}</th>
                <th style="width: 13%;" class="right">{{ $isCourier ? $t['bonus'] : $t['commission'] }}</th>
                <th style="width: 15%;" class="right">{{ $t['final'] }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>№{{ $row['order_id'] ?: '—' }} <span class="muted small">({{ $row['date'] }})</span></td>
                <td>№{{ $row['sub_order_id'] ?: '—' }}</td>
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

    <table class="summary">
        <tr>
            <td>{{ $t['orders'] }}</td>
            <td class="right">{{ $totals['orders'] }}</td>
        </tr>
        <tr>
            <td>{{ $t['products'] }}</td>
            <td class="right">{{ $totals['products'] }}</td>
        </tr>
        <tr>
            <td>{{ $t['gross'] }}</td>
            <td class="right">{{ $fmt($totals['gross']) }} {{ $currency }}</td>
        </tr>
        @if($isCourier)
            <tr>
                <td>{{ $t['base_payout'] }}</td>
                <td class="right">{{ $fmt($totals['base_payout']) }} {{ $currency }}</td>
            </tr>
            <tr>
                <td>{{ $t['bonus'] }}</td>
                <td class="right">{{ $fmt($totals['bonus']) }} {{ $currency }}</td>
            </tr>
        @else
            <tr>
                <td>{{ $t['commission'] }}</td>
                <td class="right">{{ $fmt($totals['commission']) }} {{ $currency }}</td>
            </tr>
        @endif
        <tr class="total">
            <td>{{ $t['final_amount'] }}</td>
            <td class="right">{{ $fmt($totals['net']) }} {{ $currency }}</td>
        </tr>
    </table>

    <div class="note">{{ $t['note'] }}</div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-role">{{ $t['kitobchi_rep'] }}:</div>
                <table class="sig-inner">
                    <tr>
                        <td><div class="sig-line"></div><div class="sig-caption center">{{ $t['fio'] }}</div></td>
                        <td><div class="sig-line"></div><div class="sig-caption center">{{ $t['signature'] }}</div></td>
                    </tr>
                </table>
                <div class="small muted" style="margin-top: 8px;">{{ $t['stamp'] }}</div>
            </td>
            <td>
                <div class="sig-role">{{ $ownerLabel }} {{ $t['owner_rep'] }}:</div>
                <table class="sig-inner">
                    <tr>
                        <td><div class="sig-line"></div><div class="sig-caption center">{{ $t['fio'] }}</div></td>
                        <td><div class="sig-line"></div><div class="sig-caption center">{{ $t['signature'] }}</div></td>
                    </tr>
                </table>
                <div class="small muted" style="margin-top: 8px;">{{ $t['stamp'] }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $t['brand'] }} · {{ $document_no }} · {{ $generated_at }}</div>
</body>
</html>
