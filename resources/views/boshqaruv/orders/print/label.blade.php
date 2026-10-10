<!DOCTYPE html>
<html lang="{{ $label['locale'] ?? 'uz' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $label['order_number'] }} — Label</title>
  <style>
    @page {
      size: 80mm 48mm;
      margin: 0;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    html, body {
      width: 80mm;
      height: 48mm;
      overflow: hidden;
      background: #fff;
      color: #000;
      font-family: Arial, Helvetica, sans-serif;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .sheet {
      width: 80mm;
      height: 48mm;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: #fff;
    }

    /* ── Z1: SARLAVHA (QORA) ── */
    .z1 {
      flex-shrink: 0;
      height: 8.5mm;
      background: #000;
      color: #fff;
      display: flex;
      align-items: stretch;
    }

    .z1-left {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0 2mm;
      border-right: 0.3mm solid #333;
    }

    .z1-order {
      font-size: 13.5pt;
      font-weight: 900;
      line-height: 1;
      letter-spacing: -0.3px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .z1-meta {
      font-size: 6pt;
      color: #aaa;
      margin-top: 0.5mm;
      white-space: nowrap;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .z1-right {
      flex-shrink: 0;
      width: 22mm;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0.5mm 1mm;
      gap: 0.6mm;
    }

    .z1-hub {
      font-size: 5.8pt;
      font-weight: 700;
      color: #bbb;
      text-align: center;
      white-space: nowrap;
      text-transform: uppercase;
    }

    .z1-badge {
      border: 0.4mm solid #fff;
      padding: 0.4mm 1.5mm;
      border-radius: 0.5mm;
      font-size: 6.8pt;
      font-weight: 900;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      line-height: 1;
      white-space: nowrap;
    }

    /* ── Z2: MIJOZ VA QR ── */
    .z2 {
      flex: 1;
      min-height: 0;
      display: flex;
      padding: 1.5mm 2.2mm 1mm;
      gap: 2mm;
      overflow: hidden;
    }

    .z2-info {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .z2-name {
      font-size: 11pt;
      font-weight: 900;
      line-height: 1.05;
      letter-spacing: -0.2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .z2-phone {
      font-size: 8pt;
      font-weight: 600;
      color: #222;
      margin-top: 0.5mm;
      white-space: nowrap;
    }

    .z2-addr-wrap {
      margin-top: 0.9mm;
      border-left: 0.8mm solid #000;
      padding-left: 1.2mm;
    }

    .z2-addr {
      font-size: 6.8pt;
      line-height: 1.25;
      color: #111;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .z2-qr {
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 14.5mm;
    }

    .z2-qr-img {
      width: 13mm;
      height: 13mm;
      display: block;
      image-rendering: crisp-edges;
    }

    .z2-qr-code {
      font-size: 5pt;
      color: #555;
      font-family: 'Courier New', monospace;
      margin-top: 0.3mm;
      text-align: center;
      white-space: nowrap;
    }

    /* ── Z3-SLOT: JADVAL VAQTI ── */
    .z3-slot {
      flex-shrink: 0;
      height: 4.4mm;
      border-top: 0.35mm solid #000;
      display: flex;
      align-items: center;
      padding: 0 2.2mm;
      gap: 1.2mm;
    }

    .z3s-icon {
      flex-shrink: 0;
      width: 2.8mm;
      height: 2.8mm;
      display: block;
    }

    .z3s-text {
      font-size: 6.4pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Z3-COD: NAQD OLINADI (YIRTILGAN CHEK) ── */
    .z3-cod-wrap {
      flex-shrink: 0;
      position: relative;
      padding: 1.4mm 0;
      background: #fff;
    }

    .z3-cod {
      background: #000;
      color: #fff;
      display: flex;
      align-items: center;
      padding: 0 2.2mm;
      height: 5.2mm;
      gap: 1.8mm;
      position: relative;
    }

    .z3-cod::before {
      content: '';
      position: absolute;
      top: -1.3mm;
      left: 0;
      right: 0;
      height: 1.4mm;
      background: #000;
      clip-path: polygon(
        0% 100%, 3.125% 0%, 6.25% 100%, 9.375% 0%, 12.5% 100%,
        15.625% 0%, 18.75% 100%, 21.875% 0%, 25% 100%, 28.125% 0%,
        31.25% 100%, 34.375% 0%, 37.5% 100%, 40.625% 0%, 43.75% 100%,
        46.875% 0%, 50% 100%, 53.125% 0%, 56.25% 100%, 59.375% 0%,
        62.5% 100%, 65.625% 0%, 68.75% 100%, 71.875% 0%, 75% 100%,
        78.125% 0%, 81.25% 100%, 84.375% 0%, 87.5% 100%, 90.625% 0%,
        93.75% 100%, 96.875% 0%, 100% 100%
      );
    }

    .z3-cod::after {
      content: '';
      position: absolute;
      bottom: -1.3mm;
      left: 0;
      right: 0;
      height: 1.4mm;
      background: #000;
      clip-path: polygon(
        0% 0%, 3.125% 100%, 6.25% 0%, 9.375% 100%, 12.5% 0%,
        15.625% 100%, 18.75% 0%, 21.875% 100%, 25% 0%, 28.125% 100%,
        31.25% 0%, 34.375% 100%, 37.5% 0%, 40.625% 100%, 43.75% 0%,
        46.875% 100%, 50% 0%, 53.125% 100%, 56.25% 0%, 59.375% 100%,
        62.5% 0%, 65.625% 100%, 68.75% 0%, 71.875% 100%, 75% 0%,
        78.125% 100%, 81.25% 0%, 84.375% 100%, 87.5% 0%, 90.625% 100%,
        93.75% 0%, 96.875% 100%, 100% 0%
      );
    }

    .z3c-icon {
      flex-shrink: 0;
      width: 3.2mm;
      height: 2.4mm;
    }
    .z3c-icon svg { width: 100%; height: 100%; display: block; fill: none; stroke: #fff; }

    .z3c-label {
      flex: 1;
      font-size: 7.2pt;
      font-weight: 900;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .z3c-amount {
      font-size: 9.8pt;
      font-weight: 900;
      color: #fff;
      white-space: nowrap;
    }

    /* ── Z4: TO'LOV · SUMMA · SANA ── */
    .z4 {
      flex-shrink: 0;
      height: 5.6mm;
      border-top: 0.3mm solid #000;
      display: flex;
    }

    .z3-cod-wrap + .z4 {
      border-top: none;
    }

    .z4-cell {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0 1.8mm;
      overflow: hidden;
    }

    .z4-cell + .z4-cell {
      border-left: 0.25mm solid #ddd;
    }

    .z4-lbl {
      font-size: 4.8pt;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      color: #666;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 0.6mm;
    }
    .z4-lbl svg { width: 2mm; height: 1.5mm; }

    .z4-val {
      font-size: 6.8pt;
      font-weight: 700;
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Z5: AVVALGI XARIDOR TILAGI (1 yoki 2 qator) ── */
    .z5 {
      flex-shrink: 0;
      min-height: 4.2mm;
      max-height: 6.4mm;
      border-top: 0.25mm dashed #333;
      display: flex;
      align-items: center;
      padding: 0.6mm 2mm;
      gap: 1.2mm;
      background: #fafafa;
      overflow: hidden;
    }

    .z5-badge {
      flex-shrink: 0;
      align-self: flex-start;
      margin-top: 0.2mm;
      display: inline-flex;
      align-items: center;
      gap: 0.6mm;
      background: #000;
      color: #fff;
      padding: 0.3mm 1mm;
      border-radius: 0.3mm;
      font-size: 4.8pt;
      font-weight: 900;
      letter-spacing: 0.3px;
      text-transform: uppercase;
      line-height: 1;
    }

    .z5-badge svg {
      width: 1.4mm;
      height: 1.4mm;
      fill: #fff;
      display: block;
    }

    .z5-content {
      flex: 1;
      min-width: 0;
      font-size: 5.6pt;
      line-height: 1.25;
      color: #111;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .z5-author {
      font-weight: 800;
      color: #000;
      margin-right: 0.8mm;
    }

    .z5-msg {
      font-style: italic;
      color: #222;
    }
  </style>
</head>
<body onload="window.print()">

  <svg xmlns="http://www.w3.org/2000/svg" style="display:none">
    <!-- Tilak ikonkasi -->
    <symbol id="ic-quote" viewBox="0 0 16 16">
      <path d="M3 9.5C3 7 4.5 4.5 7 3.5L7.5 4.5C5.8 5.3 4.8 6.8 4.8 8.2H7V13H3V9.5ZM10 9.5C10 7 11.5 4.5 14 3.5L14.5 4.5C12.8 5.3 11.8 6.8 11.8 8.2H14V13H10V9.5Z" fill="currentColor"/>
    </symbol>
    <!-- Naqd pul -->
    <symbol id="ic-naqd" viewBox="0 0 22 15">
      <rect x="1" y="1" width="20" height="13" rx="1.5" stroke="white" stroke-width="2" fill="none"/>
      <circle cx="11" cy="7.5" r="3" stroke="white" stroke-width="1.8" fill="none"/>
      <line x1="1" y1="5" x2="4.5" y2="5" stroke="white" stroke-width="1.8"/>
      <line x1="17.5" y1="5" x2="21" y2="5" stroke="white" stroke-width="1.8"/>
      <line x1="1" y1="10" x2="4.5" y2="10" stroke="white" stroke-width="1.8"/>
      <line x1="17.5" y1="10" x2="21" y2="10" stroke="white" stroke-width="1.8"/>
    </symbol>
    <!-- Soat -->
    <symbol id="ic-soat" viewBox="0 0 20 20">
      <circle cx="10" cy="10" r="8.5" stroke="black" stroke-width="2" fill="none"/>
      <line x1="10" y1="5" x2="10" y2="10.5" stroke="black" stroke-width="2.2" stroke-linecap="round"/>
      <line x1="10" y1="10.5" x2="13.5" y2="13" stroke="black" stroke-width="2.2" stroke-linecap="round"/>
      <circle cx="10" cy="10.5" r="1.2" fill="black"/>
    </symbol>
    <!-- Karta -->
    <symbol id="ic-karta" viewBox="0 0 22 15">
      <rect x="1" y="1.5" width="20" height="12" rx="2" stroke="#666" stroke-width="1.8" fill="none"/>
      <line x1="1" y1="6" x2="21" y2="6" stroke="#666" stroke-width="2.8"/>
      <rect x="3" y="9" width="6" height="2.5" rx=".6" fill="#666"/>
    </symbol>
    <!-- Quti -->
    <symbol id="ic-quti" viewBox="0 0 20 20">
      <path d="M10 2.5L18 7V13L10 17.5L2 13V7L10 2.5Z" stroke="#666" stroke-width="1.8" fill="none" stroke-linejoin="round"/>
      <line x1="10" y1="2.5" x2="10" y2="17.5" stroke="#666" stroke-width="1.5"/>
      <line x1="2" y1="7" x2="18" y2="7" stroke="#666" stroke-width="1.3"/>
    </symbol>
  </svg>

  <div class="sheet">
    {{-- Z1: SARLAVHA --}}
    <div class="z1">
      <div class="z1-left">
        <div class="z1-order">{{ $label['order_number'] }}</div>
        <div class="z1-meta">{{ $label['delivery_type_label'] }}</div>
      </div>
      <div class="z1-right">
        <div class="z1-hub">{{ $label['meta_hub_name'] }}</div>
        @if(($label['cod_amount'] ?? 0) > 0)
          <div class="z1-badge">NAQD</div>
        @elseif(!empty($label['schedule_window']))
          <div class="z1-badge">JADVAL</div>
        @else
          <div class="z1-badge">TEZKOR</div>
        @endif
      </div>
    </div>

    {{-- Z2: MIJOZ BLOKI --}}
    <div class="z2">
      <div class="z2-info">
        <div class="z2-name">{{ $label['customer_name'] }}</div>
        <div class="z2-phone">{{ $label['customer_phone'] }}</div>
        <div class="z2-addr-wrap">
          <div class="z2-addr">{{ $label['address'] }}</div>
        </div>
      </div>
      <div class="z2-qr">
        <img class="z2-qr-img" src="{{ $label['qr_data_uri'] }}" alt="{{ $label['label_code'] }} QR">
        <div class="z2-qr-code">{{ $label['label_code'] }}</div>
      </div>
    </div>

    {{-- Z3a: REJALASHTIRILGAN JADVAL VAQTI (agar mavjud bo'lsa) --}}
    @if(!empty($label['schedule_window']))
      <div class="z3-slot">
        <div class="z3s-icon">
          <svg viewBox="0 0 20 20"><use href="#ic-soat"/></svg>
        </div>
        <div class="z3s-text">Yetkazish: {{ $label['schedule_window'] }}</div>
      </div>
    @endif

    {{-- Z3b: NAQD OLINADI (agar COD bo'lsa, yirtilgan chek foni) --}}
    @if(($label['cod_amount'] ?? 0) > 0)
      <div class="z3-cod-wrap">
        <div class="z3-cod">
          <div class="z3c-icon">
            <svg viewBox="0 0 22 15"><use href="#ic-naqd"/></svg>
          </div>
          <div class="z3c-label">Naqd olinadi</div>
          <div class="z3c-amount">{{ number_format((int) $label['cod_amount'], 0, '.', ' ') }} so‘m</div>
        </div>
      </div>
    @endif

    {{-- Z4: TO'LOV · SUMMA · SANA --}}
    <div class="z4">
      <div class="z4-cell">
        <div class="z4-lbl"><svg viewBox="0 0 22 15"><use href="#ic-karta"/></svg> To‘lov</div>
        <div class="z4-val">{{ $label['payment_method'] }}</div>
      </div>
      <div class="z4-cell">
        <div class="z4-lbl"><svg viewBox="0 0 20 20"><use href="#ic-quti"/></svg> Jami summa</div>
        <div class="z4-val">{{ number_format((int) ($label['total_amount'] ?? 0), 0, '.', ' ') }} so‘m</div>
      </div>
      <div class="z4-cell">
        <div class="z4-lbl">Buyurtma sanasi</div>
        <div class="z4-val">{{ $label['created_at_pretty'] ?: '—' }}</div>
      </div>
    </div>

    {{-- Z5: AVVALGI XARIDOR TILAGI (1 yoki 2 qator) --}}
    <div class="z5">
      <div class="z5-badge">
        <svg viewBox="0 0 16 16"><use href="#ic-quote"/></svg>
        <span>TILAK</span>
      </div>
      <div class="z5-content">
        <span class="z5-author">{{ $label['prev_buyer_author'] ?? 'Kitobxon' }}:</span>
        <span class="z5-msg">«{{ $label['prev_buyer_wish'] ?? $label['delight_message'] }}»</span>
      </div>
    </div>
  </div>

</body>
</html>
