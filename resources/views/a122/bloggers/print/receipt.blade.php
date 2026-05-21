<!DOCTYPE html>
<html lang="{{ $receipt['locale'] }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $receipt['shipment_number'] }} — Blogger Label</title>
  <style>
    @page { size: 80mm 48mm; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: #fff; color: #111827; }
    .sheet { width: 80mm; height: 48mm; padding: 3mm; display: flex; flex-direction: column; gap: 1.25mm; }
    .row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5mm; }
    .code { font-size: 12.8px; font-weight: 700; line-height: 1.03; }
    .status { max-width: 30mm; text-align: right; }
    .status__title { font-size: 9.1px; font-weight: 700; line-height: 1.04; color: #111827; }
    .status__sub { margin-top: .35mm; font-size: 8.1px; color: #4b5563; line-height: 1.04; }
    .person { display: flex; flex-direction: column; gap: .35mm; margin-top: .45mm; }
    .name { font-size: 11.6px; font-weight: 700; line-height: 1.04; }
    .phone { font-size: 9.4px; line-height: 1.1; }
    .social { font-size: 8px; line-height: 1.06; color: #4b5563; }
    .content { display: grid; grid-template-columns: 1fr 1fr; gap: 1.4mm; flex: 1; min-height: 0; }
    .box { border: 1px solid #111827; border-radius: 3px; padding: 1.45mm 1.8mm; min-height: 0; }
    .box--address { display: flex; flex-direction: column; }
    .box--items { display: flex; flex-direction: column; }
    .label { display: block; font-size: 7.6px; color: #4b5563; margin-bottom: .55mm; text-transform: uppercase; line-height: 1; }
    .address { font-size: 8.8px; line-height: 1.1; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
    .items { display: flex; flex-direction: column; gap: .45mm; }
    .item { display: flex; align-items: flex-start; gap: 1mm; font-size: 8.4px; line-height: 1.08; }
    .dot { width: 3.2mm; flex: 0 0 3.2mm; font-size: 8px; font-weight: 700; color: #111827; }
    .item-name { min-width: 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; }
    .more { margin-top: .3mm; font-size: 8px; font-weight: 700; color: #374151; }
    .footer { border: 1px solid #111827; border-radius: 3px; padding: 1.35mm 1.8mm 1.5mm; min-height: 8.6mm; display: flex; flex-direction: column; justify-content: center; }
    .time { font-size: 7.6px; color: #4b5563; text-transform: uppercase; line-height: 1.05; text-align: center; }
    .message { margin-top: .65mm; text-align: center; font-size: 10.4px; line-height: 1.1; font-weight: 800; color: #374151; }
  </style>
</head>
<body onload="window.print()">
  <div class="sheet">
    <div class="row">
      <div class="code">{{ $receipt['shipment_number'] }}</div>
      <div class="status">
        <div class="status__title">{{ $receipt['status_label'] }}</div>
        <div class="status__sub">{{ mb_strtoupper($receipt['scheduled_at_pretty'] ?: '—') }}</div>
      </div>
    </div>

    <div class="person">
      <div class="name">{{ $receipt['blogger_name'] }}</div>
      <div class="phone">{{ $receipt['phone'] }}</div>
      @if($receipt['social_primary'])
        <div class="social">{{ $receipt['social_primary'] }}</div>
      @endif
    </div>

    <div class="content">
      <div class="box box--address">
        <span class="label">Address</span>
        <div class="address">{{ $receipt['address'] }}</div>
      </div>
      <div class="box box--items">
        <span class="label">{{ $receipt['items_label'] }}</span>
        <div class="items">
          @foreach($receipt['items_preview'] as $item)
            <div class="item">
              <span class="dot">{{ $item['index'] }}.</span>
              <span class="item-name">{{ $item['name'] }}</span>
            </div>
          @endforeach
          @if(($receipt['items_remaining'] ?? 0) > 0)
            <div class="more">+{{ $receipt['items_remaining'] }} ta</div>
          @endif
        </div>
      </div>
    </div>

    <div class="footer">
      <div class="time">{{ $receipt['printed_at_label'] }}: {{ $receipt['scheduled_at_raw'] }}</div>
      <div class="message">{{ $receipt['message'] }}</div>
    </div>
  </div>
</body>
</html>
