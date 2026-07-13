@php
    $fmt = fn ($value) => number_format((int) $value, 0, '.', ' ');
    $rep = fn (string $text, array $map) => strtr($text, $map);
    $logoPath = public_path('images/logo/logo_black.png');
    $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $common = [
        ':number' => $number,
        ':date' => $contractDate ?: '—',
        ':letterDate' => $letterDate,
        ':orderId' => (string) ($orderId ?? '—'),
        ':lender' => $lender['name'],
        ':overdueTotal' => $fmt($overdueTotal),
        ':maxDays' => (string) $maxDaysOverdue,
        ':remaining' => $fmt($remainingTotal),
        ':paid' => $fmt($paidTotal),
        ':total' => $fmt($contractTotal),
        ':days' => (string) $demandDays,
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 52px 52px 60px; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10.5px; line-height: 1.55; }
    .addr-table { width: 100%; margin-bottom: 18px; }
    .addr-table td { vertical-align: top; font-size: 10px; }
    .addr-right { width: 46%; }
    .title { text-align: center; font-size: 14px; font-weight: bold; letter-spacing: 1px; margin: 6px 0 2px; }
    .subtitle { text-align: center; font-size: 9.5px; color: #222; margin-bottom: 16px; }
    p.just { text-align: justify; margin: 8px 0; }
    table.grid { width: 100%; border-collapse: collapse; margin: 8px 0; }
    table.grid th, table.grid td { border: 1px solid #000; padding: 4px 6px; font-size: 9.6px; }
    table.grid th { background: #efefef; font-weight: bold; text-align: center; }
    .r { text-align: right; }
    .c { text-align: center; }
    .b { font-weight: bold; }
    .demand-box { border: 1.5px solid #000; padding: 10px 12px; margin: 12px 0; }
    .demand-title { font-weight: bold; font-size: 11px; margin-bottom: 4px; }
    .req-box { border: 1px solid #000; padding: 8px 10px; margin: 10px 0; font-size: 9.8px; }
    .req-title { font-weight: bold; margin-bottom: 4px; text-transform: uppercase; font-size: 9.6px; }
    .sign-table { width: 100%; margin-top: 24px; }
    .sign-table td { font-size: 10.5px; }
    .footer-note { margin-top: 16px; font-size: 8.6px; color: #333; border-top: 1px solid #999; padding-top: 6px; text-align: justify; }
</style>
</head>
<body>

<table class="addr-table">
    <tr>
        <td style="vertical-align: top;">
            @if($logo)
                <img src="{{ $logo }}" alt="Kitobchi" style="height: 28px;">
                <div style="font-size: 8.6px; color: #333; margin-top: 4px;">kitobchi.com</div>
            @endif
        </td>
        <td class="addr-right">
            <div><span class="b">{{ $t['to'] }}:</span> {{ $buyer['name'] }}</div>
            @if($buyer['address'])<div><span class="b">{{ $t['address'] }}:</span> {{ $buyer['address'] }}</div>@endif
            @if($buyer['phone'])<div><span class="b">{{ $t['phone'] }}:</span> {{ $buyer['phone'] }}</div>@endif
            <div style="margin-top:6px"><span class="b">{{ $t['from'] }}:</span> {{ $lender['name'] }}</div>
            <div>{{ $t['inn'] }}: {{ $lender['inn'] }}</div>
            @if($lender['address'])<div>{{ $t['address'] }}: {{ $lender['address'] }}</div>@endif
            @if($lender['email'])<div>E-mail: {{ $lender['email'] }}</div>@endif
        </td>
    </tr>
</table>

<div class="title">{{ $t['title'] }}</div>
<div class="subtitle">{{ $t['subtitle'] }}</div>

<p class="just">{{ $rep($t['body_1'], $common) }}</p>
<p class="just">{{ $rep($t['body_2'], $common) }}</p>

<table class="grid">
    <thead>
        <tr>
            <th style="width:14%">{{ $t['tbl_no'] }}</th>
            <th style="width:24%">{{ $t['tbl_due'] }}</th>
            <th style="width:28%">{{ $t['tbl_amount'] }}</th>
            <th>{{ $t['tbl_days'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($overdue as $row)
        <tr>
            <td class="c">{{ $row['sequence'] }}</td>
            <td class="c">{{ $row['due_at'] ?: '—' }}</td>
            <td class="r">{{ $fmt($row['amount']) }}</td>
            <td class="c">{{ $row['days_overdue'] }} {{ $t['days_unit'] }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2" class="b">{{ $t['total_overdue'] }}</td>
            <td class="r b">{{ $fmt($overdueTotal) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<p class="just">{{ $rep($t['debt_info'], $common) }}</p>

<div class="demand-box">
    <div class="demand-title">{{ $t['demand_title'] }}</div>
    <div style="text-align: justify;">{{ $rep($t['demand_text'], $common) }}</div>
</div>

<p class="just">{{ $t['warning'] }}</p>

<div class="req-box">
    <div class="req-title">{{ $t['requisites'] }}</div>
    <div class="b">{{ $lender['name'] }}</div>
    <div>{{ $t['inn'] }}: {{ $lender['inn'] }}</div>
    <div>{{ $t['account'] }}: {{ $lender['account'] }}</div>
    <div>{{ $t['mfo'] }}: {{ $lender['mfo'] }} · {{ $t['bank'] }}: {{ $lender['bank'] }}</div>
    <div class="b" style="margin-top:4px">{{ $rep($t['contract_ref'], $common) }}</div>
</div>

<table class="sign-table">
    <tr>
        <td>
            {{ $t['sign'] }}<br>
            <span class="b">{{ $lender['name'] }}</span><br><br>
            _______________________ <span style="font-size:9px">({{ $locale === 'ru' ? 'подпись' : 'imzo' }})</span>
        </td>
        <td class="r" style="vertical-align: bottom;">
            {{ $t['date_label'] }}: <span class="b">{{ $letterDate }}</span>
        </td>
    </tr>
</table>

<div class="footer-note">{{ $rep($t['footer'], $common) }}</div>

</body>
</html>
