<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $label['order_number'] }} — Label</title>
  <style>
    @page { size: 80mm 48mm; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: #fff; color: #111827; }
    .sheet { width: 80mm; height: 48mm; padding: 3mm; display: flex; flex-direction: column; gap: 2mm; }
    .row { display: flex; align-items: flex-start; justify-content: space-between; gap: 2mm; }
    .order { font-size: 13px; font-weight: 700; line-height: 1.1; }
    .hub { display: flex; justify-content: flex-end; }
    .hub-note { max-width: 31mm; text-align: right; }
    .hub-note__title { font-size: 9.5px; font-weight: 700; color: #111827; line-height: 1.1; }
    .hub-note__sub { margin-top: .8mm; font-size: 8.5px; color: #4b5563; line-height: 1.15; }
    .name { font-size: 12px; font-weight: 700; line-height: 1.15; }
    .phone { font-size: 10px; line-height: 1.15; }
    .address { flex: 1; border: 1px solid #111827; border-radius: 3px; padding: 2mm; font-size: 10px; line-height: 1.2; min-height: 13mm; }
    .footer { display: grid; grid-template-columns: 1fr 1fr; gap: 2mm; }
    .box { border: 1px solid #111827; border-radius: 3px; padding: 1.5mm 2mm; min-height: 8mm; }
    .label { display: block; font-size: 8px; color: #4b5563; margin-bottom: 1mm; text-transform: uppercase; }
    .value { font-size: 10px; font-weight: 700; line-height: 1.15; }
    .hint { font-size: 8.5px; color: #4b5563; margin-top: .8mm; line-height: 1.15; }
    .qr { margin-top: auto; display: flex; align-items: center; justify-content: center; min-height: 1mm; }
    .center-note { font-size: 11px; color: #374151; line-height: 1.22; text-align: center; font-weight: 800; }
  </style>
</head>
<body onload="window.print()">
  <div class="sheet">
    <div class="row">
      <div class="order">{{ $label['order_number'] }}</div>
      <div class="hub">
        <div class="hub-note">
          <div class="hub-note__title">{{ $label['meta_hub_name'] }}</div>
          <div class="hub-note__sub">{{ $label['delivery_type_label'] }}</div>
        </div>
      </div>
    </div>

    <div>
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

    <div class="qr">
      <div class="center-note">{{ $label['delight_message'] }}</div>
    </div>
  </div>
</body>
</html>
