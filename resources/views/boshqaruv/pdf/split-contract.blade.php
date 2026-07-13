@php
    $fmt = fn ($value) => number_format((int) $value, 0, '.', ' ');
    $rep = fn (string $text, array $map) => strtr($text, $map);
    $logoPath = public_path('images/logo/logo_black.png');
    $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 46px 46px 58px; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10px; line-height: 1.45; }
    .doc-head { text-align: center; margin-bottom: 4px; }
    .doc-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; }
    .doc-meta { width: 100%; margin: 10px 0 4px; font-size: 10px; }
    .doc-meta td { padding: 0; }
    .preamble { text-align: justify; margin: 8px 0 12px; }
    h3.sec { font-size: 10.5px; font-weight: bold; margin: 14px 0 6px; text-transform: uppercase; }
    table.grid { width: 100%; border-collapse: collapse; margin: 6px 0; }
    table.grid th, table.grid td { border: 1px solid #000; padding: 4px 6px; font-size: 9.5px; }
    table.grid th { background: #efefef; font-weight: bold; text-align: center; }
    .r { text-align: right; }
    .c { text-align: center; }
    .b { font-weight: bold; }
    table.plain { width: 100%; border-collapse: collapse; }
    table.plain td { padding: 2px 4px; vertical-align: top; font-size: 9.8px; }
    ol.terms { margin: 4px 0 4px 16px; padding: 0; }
    ol.terms li { margin-bottom: 4px; text-align: justify; }
    .just { text-align: justify; }
    .party-box { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .party-box td { width: 50%; border: 1px solid #000; padding: 8px 10px; vertical-align: top; font-size: 9.6px; }
    .party-title { font-weight: bold; font-size: 10px; margin-bottom: 5px; text-transform: uppercase; }
    .muted { color: #333; }
    .footer-note { margin-top: 14px; font-size: 8.6px; color: #333; border-top: 1px solid #999; padding-top: 6px; text-align: justify; }
    .accept-badge { border: 1px solid #000; padding: 5px 8px; font-size: 9px; margin-top: 8px; text-align: center; font-weight: bold; }
</style>
</head>
<body>

<div class="doc-head">
    @if($logo)
        <img src="{{ $logo }}" alt="Kitobchi" style="height: 26px; margin-bottom: 10px;">
    @endif
    <div class="doc-title">{{ $t['title'] }}</div>
</div>
<table class="doc-meta">
    <tr>
        <td class="b">{{ $t['number'] }} {{ $number }}</td>
        <td class="r">{{ $t['date'] }}: <span class="b">{{ $date }}</span></td>
    </tr>
</table>

<div class="preamble">{{ $t['preamble'] }}</div>

<table class="plain">
    <tr>
        <td style="width:32%" class="b">{{ $t['seller'] }}:</td>
        <td>{{ $lender['name'] }}, {{ $t['inn'] }}: {{ $lender['inn'] }}</td>
    </tr>
    <tr>
        <td class="b">{{ $t['buyer'] }}:</td>
        <td>{{ $buyer['name'] }} — {{ $t['buyer_phone'] }}: {{ $buyer['phone'] ?: '—' }}, {{ $t['buyer_account'] }}: {{ $buyer['userId'] }}@if($buyer['address']), {{ $t['buyer_address'] }}: {{ $buyer['address'] }}@endif</td>
    </tr>
</table>

<h3 class="sec">{{ $t['s1_title'] }}</h3>
<div class="just">{{ $rep($t['s1_text'], [':orderId' => (string) ($orderId ?? '—')]) }}</div>

@if(count($items))
<table class="grid">
    <thead>
        <tr>
            <th style="width:6%">№</th>
            <th>{{ $t['goods'] }}</th>
            <th style="width:10%">{{ $t['qty'] }}</th>
            <th style="width:18%">{{ $t['price'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $item['name'] }}</td>
            <td class="c">{{ $item['quantity'] }}</td>
            <td class="r">{{ $fmt($item['price']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<h3 class="sec">{{ $t['s2_title'] }}</h3>
<table class="grid">
    <tbody>
        <tr>
            <td>{{ $t['s2_principal'] }}</td>
            <td class="r" style="width:24%">{{ $fmt($sums['principal']) }} {{ $t['som'] }}</td>
        </tr>
        @if(($sums['deliveryFee'] + $sums['packagingFee']) > 0)
        <tr>
            <td>{{ $t['s2_delivery'] }}</td>
            <td class="r">{{ $fmt($sums['deliveryFee'] + $sums['packagingFee']) }} {{ $t['som'] }}</td>
        </tr>
        @endif
        <tr>
            <td>{{ $rep($t['s2_interest'], [':monthly' => rtrim(rtrim(number_format($plan['monthlyPercent'], 2, '.', ''), '0'), '.'), ':months' => (string) $plan['months'], ':total' => rtrim(rtrim(number_format($plan['totalPercent'], 2, '.', ''), '0'), '.')]) }}</td>
            <td class="r">{{ $fmt($sums['interest']) }} {{ $t['som'] }}</td>
        </tr>
        <tr>
            <td class="b">{{ $t['s2_total'] }}</td>
            <td class="r b">{{ $fmt($sums['total']) }} {{ $t['som'] }}</td>
        </tr>
        @if($sums['upfront'] > 0)
        <tr>
            <td>{{ $t['s2_upfront'] }}</td>
            <td class="r">{{ $fmt($sums['upfront']) }} {{ $t['som'] }}</td>
        </tr>
        @endif
    </tbody>
</table>
<div class="just">{{ $t['s2_text'] }}</div>

<h3 class="sec">{{ $t['s3_title'] }}</h3>
<table class="grid">
    <thead>
        <tr>
            <th style="width:8%">{{ $t['sch_no'] }}</th>
            <th style="width:24%">{{ $t['sch_date'] }}</th>
            <th style="width:24%">{{ $t['sch_amount'] }}</th>
            <th>{{ $t['sch_note'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($schedule as $row)
        <tr>
            <td class="c">{{ $row['sequence'] }}</td>
            <td class="c">{{ $row['due_at'] ?: '—' }}</td>
            <td class="r">{{ $fmt($row['amount']) }}</td>
            <td class="c muted">
                @if($row['is_upfront']){{ $t['sch_upfront'] }}@endif
                @if($row['status'] === 'paid') {{ $row['is_upfront'] ? '·' : '' }} {{ $t['sch_paid'] }}@if($row['paid_at']) ({{ $row['paid_at'] }})@endif @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2" class="b">{{ $t['s2_total'] }}</td>
            <td class="r b">{{ $fmt($sums['total']) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<h3 class="sec">{{ $t['s4_title'] }}</h3>
<ol class="terms">
    @foreach($t['s4_items'] as $item)
    <li>{{ $item }}</li>
    @endforeach
</ol>

<h3 class="sec">{{ $t['s5_title'] }}</h3>
<div class="just">{{ $t['s5_text'] }}</div>

<h3 class="sec">{{ $t['s6_title'] }}</h3>
<div class="just">{{ $rep($t['s6_text'], [':acceptedAt' => $acceptedAtFull, ':userId' => (string) $buyer['userId'], ':phone' => $buyer['phone'] ?: '—']) }}</div>

<h3 class="sec">{{ $t['s7_title'] }}</h3>
<div class="just">{{ $t['s7_text'] }}</div>

<h3 class="sec">{{ $t['s8_title'] }}</h3>
<table class="party-box">
    <tr>
        <td>
            <div class="party-title">{{ $t['seller'] }}</div>
            <div class="b">{{ $lender['name'] }}</div>
            <div>{{ $t['inn'] }}: {{ $lender['inn'] }}</div>
            <div>{{ $t['account'] }}: {{ $lender['account'] }}</div>
            <div>{{ $t['mfo'] }}: {{ $lender['mfo'] }}</div>
            <div>{{ $t['bank'] }}: {{ $lender['bank'] }}</div>
            @if($lender['email'])<div>{{ $t['email'] }}: {{ $lender['email'] }}</div>@endif
            @if($lender['phone'])<div>{{ $t['buyer_phone'] }}: {{ $lender['phone'] }}</div>@endif
            @if($lender['address'])<div>{{ $t['buyer_address'] }}: {{ $lender['address'] }}</div>@endif
        </td>
        <td>
            <div class="party-title">{{ $t['buyer'] }}</div>
            <div class="b">{{ $buyer['name'] }}</div>
            <div>{{ $t['buyer_phone'] }}: {{ $buyer['phone'] ?: '—' }}</div>
            <div>{{ $t['buyer_account'] }}: {{ $buyer['userId'] }}</div>
            @if($buyer['address'])<div>{{ $t['buyer_address'] }}: {{ $buyer['address'] }}</div>@endif
            <div class="accept-badge">{{ $t['accepted_mark'] }}<br>{{ $acceptedAtFull }}</div>
        </td>
    </tr>
</table>

<div class="footer-note">
    {{ $t['footer'] }} · {{ $t['generated'] }}: {{ $generatedAt }} · {{ $t['number'] }} {{ $number }}
</div>

</body>
</html>
