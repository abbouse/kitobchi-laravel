@extends('panel.layouts.panel')
@section('title', 'Import: Kitoblar')
@section('page-title', 'Kitoblar import')

@section('content')

<div class="page-header fade-up d-flex align-items-center gap-3 mb-4">
  <a href="{{ route('panel.books.index') }}" class="btn-p ghost icon">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h1 class="page-title">Import</h1>
    <p class="page-sub">Excel yoki CSV fayldan kitoblar import qilish</p>
  </div>
</div>

<div class="row g-3">

  {{-- Yuklash --}}
  <div class="col-xl-6 fade-up">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Fayl yuklash</div>
        <div class="dash-card-sub">.xlsx, .xls, .csv</div>
      </div>
      <div class="dash-card-body">
        <form method="POST" action="{{ route('panel.books.import.post') }}"
              enctype="multipart/form-data">
          @csrf

          <div style="border:2px dashed var(--p-border);border-radius:10px;
                      padding:36px;text-align:center;cursor:pointer;
                      transition:border-color .2s"
               onclick="document.getElementById('importFile').click()"
               onmouseenter="this.style.borderColor='var(--p-accent)'"
               onmouseleave="this.style.borderColor='var(--p-border)'">
            <i class="bi bi-cloud-upload"
               style="font-size:40px;color:var(--p-hint);display:block;margin-bottom:10px"></i>
            <div style="font-size:14px;font-weight:500;color:var(--p-text)">Fayl tanlash</div>
            <div style="font-size:12px;color:var(--p-hint);margin-top:4px">yoki bu yerga tashlang</div>
            <div id="fileNameDisplay"
                 style="margin-top:10px;font-size:13px;font-weight:500;color:var(--p-accent)"></div>
          </div>

          <input type="file" id="importFile" name="file"
                 accept=".xlsx,.xls,.csv" style="display:none"
                 onchange="document.getElementById('fileNameDisplay').textContent = this.files[0]?.name || ''">

          @error('file')
          <div style="font-size:12px;color:var(--p-danger);margin-top:8px">{{ $message }}</div>
          @enderror

          <button type="submit" class="btn-p mt-3" style="width:100%">
            <i class="bi bi-upload"></i> Import qilish
          </button>
        </form>

        {{-- Flash xabarlar --}}
        @if(session('success'))
        <div class="p-alert success mt-3">
          <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="p-alert danger mt-3">
          <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Format qo'llanmasi --}}
  <div class="col-xl-6 fade-up">
    <div class="p-card">
      <div class="dash-card-head">
        <div class="dash-card-title">Fayl formati</div>
        <div class="dash-card-sub">Birinchi qatordagi sarlavhalar</div>
      </div>
      <div class="dash-card-body">
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr>
                <th>Ustun nomi</th>
                <th>Ma'lumot turi</th>
                <th>Majburiy</th>
              </tr>
            </thead>
            <tbody>
              @foreach([
                ['name',        'Kitob nomi',      'string',  true],
                ['author',      'Muallif',         'string',  true],
                ['category_id', 'Kategoriya ID',   'integer', true],
                ['price',       'Narx (UZS)',       'integer', true],
                ['description', 'Tavsif',           'text',    true],
                ['lang',        'Til (Oʻzbek...)',  'string',  false],
                ['year',        'Nashr yili',       'integer', false],
                ['pages',       'Sahifalar soni',   'integer', false],
              ] as [$col, $label, $type, $required])
              <tr>
                <td>
                  <code style="font-family:'DM Mono',monospace;font-size:12px;
                               background:var(--p-elevated);padding:2px 8px;
                               border-radius:4px;color:var(--p-accent)">{{ $col }}</code>
                </td>
                <td style="font-size:12px;color:var(--p-muted)">{{ $label }}</td>
                <td>
                  @if($required)
                    <span class="s-pill danger" style="font-size:10px">Majburiy</span>
                  @else
                    <span class="s-pill muted" style="font-size:10px">Ixtiyoriy</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Eslatma --}}
        <div style="margin-top:16px;padding:12px 14px;background:var(--p-warning-d);
                    border-radius:8px;border:1px solid rgba(245,166,35,.2)">
          <div style="font-size:12px;color:var(--p-warning);display:flex;align-items:flex-start;gap:8px">
            <i class="bi bi-info-circle-fill" style="margin-top:1px;flex-shrink:0"></i>
            <div>
              <strong>Eslatma:</strong> Import qilingan kitoblar avtomatik ravishda
              <code style="background:rgba(245,166,35,.2);padding:1px 5px;border-radius:3px">is_approved = 0</code>
              (kutilmoqda) holatida saqlanadi va moderatsiyadan o'tkazilishi kerak.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection