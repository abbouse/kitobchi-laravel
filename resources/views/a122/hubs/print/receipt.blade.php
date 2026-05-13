<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $receipt['order_number'] }} — Receipt</title>
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
    .total-row td { font-weight: 700; padding-top: 2mm; }
  </style>
</head>
<body onload="window.print()">
  <div class="sheet">
    <div class="center">
      <div class="title">Kitobchi Hub Receipt</div>
      <div class="muted">{{ $receipt['hub_name'] ?: 'Hub' }}</div>
      <div class="muted">{{ $receipt['order_number'] }} · {{ $receipt['created_at'] ?: '—' }}</div>
    </div>

    <div class="block">
      <div><strong>Mijoz:</strong> {{ $receipt['customer_name'] }}</div>
      <div><strong>To‘lov:</strong> {{ $receipt['payment_method'] }}</div>
      @if(($receipt['cod_amount'] ?? 0) > 0)
        <div><strong>Naqd olinadi:</strong> {{ number_format((int) $receipt['cod_amount'], 0, '.', ' ') }} UZS</div>
      @endif
    </div>

    <div class="line"></div>

    <table>
      <thead>
        <tr>
          <th>Mahsulot</th>
          <th>Soni</th>
          <th>Jami</th>
        </tr>
      </thead>
      <tbody>
        @foreach($receipt['items'] as $item)
          <tr>
            <td>{{ $item['title'] }}</td>
            <td>{{ $item['qty'] }}</td>
            <td>{{ number_format((float) $item['total'], 0, '.', ' ') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="line"></div>

    <table>
      <tbody>
        <tr><td>Subtotal</td><td>{{ number_format((int) $receipt['subtotal'], 0, '.', ' ') }}</td></tr>
        <tr><td>Delivery</td><td>{{ number_format((int) $receipt['delivery_amount'], 0, '.', ' ') }}</td></tr>
        <tr><td>Discount</td><td>-{{ number_format((int) $receipt['discount_amount'], 0, '.', ' ') }}</td></tr>
        <tr class="total-row"><td>Jami</td><td>{{ number_format((int) $receipt['total_amount'], 0, '.', ' ') }}</td></tr>
      </tbody>
    </table>

    <div class="line"></div>
    <div class="center muted">Hub ichki packing slip. Printer: 80mm thermal.</div>
  </div>
</body>
</html>
