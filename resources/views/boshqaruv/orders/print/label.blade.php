<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $label['order_number'] }} — Label</title>
  <style>
    @page { size: 80mm 48mm; margin: 0; }
    * { box-sizing: border-box; }
    html, body { width: 80mm; height: 48mm; overflow: hidden; }
    body { margin: 0; font-family: Arial, sans-serif; background: #fff; color: #111827; }
    .sheet { position: relative; width: 80mm; height: 48mm; overflow: hidden; }
    .header { position: absolute; top: 2.2mm; right: 2.6mm; left: 2.6mm; height: 14.8mm; }
    .header-copy { position: absolute; top: .35mm; right: 17mm; left: 0; overflow: hidden; }
    .order { font-size: 14.5px; font-weight: 800; line-height: 1; letter-spacing: -.15px; }
    .hub-note { margin-top: 1.45mm; overflow: hidden; }
    .hub-note__title { font-size: 9.4px; font-weight: 700; color: #111827; line-height: 1.06; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hub-note__sub { margin-top: .45mm; font-size: 8.4px; color: #4b5563; line-height: 1.05; }
    .qr-panel { position: absolute; top: 0; right: 0; width: 15mm; height: 14.8mm; text-align: center; overflow: hidden; }
    .qr-image { display: block; width: 13.4mm; height: 13.4mm; margin: 0 auto; object-fit: contain; image-rendering: crisp-edges; }
    .qr-code { width: 15mm; margin-top: .1mm; overflow: hidden; color: #374151; font-size: 5.8px; line-height: 1; text-align: center; white-space: nowrap; text-overflow: ellipsis; }
    .customer { position: absolute; top: 17.2mm; right: 2.6mm; left: 2.6mm; height: 5.8mm; overflow: hidden; }
    .name { font-size: 11.4px; font-weight: 700; line-height: 1.02; }
    .phone { margin-top: .25mm; font-size: 9.4px; line-height: 1.08; }
    .address { position: absolute; top: 23.7mm; right: 2.6mm; left: 2.6mm; height: 8.4mm; border: 1px solid #111827; border-radius: 3px; padding: 1.15mm 1.7mm; font-size: 9.2px; line-height: 1.06; overflow: hidden; }
    .footer { position: absolute; top: 32.9mm; right: 2.6mm; left: 2.6mm; height: 7mm; }
    .box { position: absolute; top: 0; width: 36.7mm; height: 7mm; border: 1px solid #111827; border-radius: 3px; padding: .9mm 1.6mm; overflow: hidden; }
    .box:first-child { left: 0; }
    .box:last-child { left: 38.1mm; }
    .label { display: block; font-size: 7.6px; color: #4b5563; margin-bottom: .45mm; text-transform: uppercase; }
    .value { font-size: 9.6px; font-weight: 700; line-height: 1.03; }
    .message { position: absolute; right: 2.6mm; bottom: 1.55mm; left: 2.6mm; height: 4.5mm; overflow: hidden; text-align: center; }
    .center-note { font-size: 8.8px; color: #374151; line-height: 4.5mm; text-align: center; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media print {
      html, body, .sheet { width: 80mm; height: 48mm; }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="sheet">
    <div class="header">
      <div class="header-copy">
        <div class="order">{{ $label['order_number'] }}</div>
        <div class="hub-note">
          <div class="hub-note__title">{{ $label['meta_hub_name'] }}</div>
          <div class="hub-note__sub">{{ $label['delivery_type_label'] }}</div>
        </div>
      </div>
      <div class="qr-panel">
        <img class="qr-image" src="{{ $label['qr_data_uri'] }}" alt="{{ $label['label_code'] }} QR">
        <div class="qr-code">{{ $label['label_code'] }}</div>
      </div>
    </div>

    <div class="customer">
      <div class="name">{{ $label['customer_name'] }}</div>
      <div class="phone">{{ $label['customer_phone'] }}</div>
    </div>

    <div class="address">{{ $label['address'] }}</div>

    <div class="footer">
      <div class="box">
        <span class="label">To‘lov</span>
        <div class="value">
          {{ $label['payment_method'] }}
          @if(($label['cod_amount'] ?? 0) > 0)
            · {{ number_format((int) $label['cod_amount'], 0, '.', ' ') }}
          @endif
        </div>
      </div>
      <div class="box">
        <span class="label">{{ mb_strtoupper($label['created_at_pretty'] ?: '—') }}</span>
        <div class="value">{{ number_format((int) ($label['total_amount'] ?? 0), 0, '.', ' ') }} so‘m</div>
      </div>
    </div>

    <div class="message">
      <div class="center-note">{{ $label['delight_message'] }}</div>
    </div>
  </div>
</body>
</html>
