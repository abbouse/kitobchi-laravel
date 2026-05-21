<!DOCTYPE html>
<html lang="{{ $receipt['locale'] }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $receipt['shipment_number'] }} — Blogger Receipt</title>
  <style>
    @page { size: 80mm auto; margin: 3mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: #111827; background: #fff; }
    .sheet { width: 74mm; margin: 0 auto; }
    .center { text-align: center; }
    .title { font-size: 15px; font-weight: 700; }
    .muted { color: #4b5563; font-size: 10px; }
    .block { margin-top: 3mm; }
    .line { border-top: 1px dashed #111827; margin: 2.5mm 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { padding: 1.2mm 0; vertical-align: top; }
    th { text-align: left; font-size: 9px; color: #4b5563; font-weight: 700; }
    td:last-child, th:last-child { text-align: right; }
    .tag { display: inline-block; margin-top: 1.4mm; padding: 1mm 2.3mm; border-radius: 999px; background: #f3f4f6; font-size: 9px; font-weight: 700; }
    .message { margin-top: 3.2mm; text-align: center; font-size: 11px; line-height: 1.25; font-weight: 700; color: #374151; }
    .social { margin-top: 1.2mm; font-size: 9.4px; color: #374151; }
  </style>
</head>
<body onload="window.print()">
  <div class="sheet">
    <div class="center">
      <div class="title">{{ $receipt['title'] }}</div>
      <div class="muted">{{ $receipt['shipment_number'] }} · {{ $receipt['scheduled_at_pretty'] }}</div>
      <div class="tag">{{ $receipt['status_label'] }}</div>
    </div>

    <div class="block">
      <div><strong>{{ $receipt['blogger_name'] }}</strong></div>
      <div class="muted">{{ $receipt['phone'] }}</div>
      <div class="muted">{{ $receipt['address'] }}</div>
      @if(!empty($receipt['social_links']))
        <div class="social">
          @foreach($receipt['social_links'] as $link)
            <div><strong>{{ $link['label'] }}:</strong> {{ $link['value'] }}</div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="line"></div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>{{ $receipt['item_name_label'] }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($receipt['items'] as $item)
          <tr>
            <td>{{ $item['index'] }}</td>
            <td>{{ $item['name'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    <div class="muted center">{{ $receipt['printed_at_label'] }}: {{ $receipt['scheduled_at_raw'] }}</div>
    @if($receipt['delivered_at'])
      <div class="muted center" style="margin-top: 1.2mm;">{{ $receipt['delivered_at'] }}</div>
    @endif

    <div class="message">{{ $receipt['message'] }}</div>
  </div>
</body>
</html>
