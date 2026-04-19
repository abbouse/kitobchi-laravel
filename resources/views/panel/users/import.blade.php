@extends('panel.layouts.panel')
@section('title','Import: Foydalanuvchilar')
@section('page-title','Foydalanuvchilar import')
@section('breadcrumb','Panel / Foydalanuvchilar / Import')

@section('content')

<x-panel.page-header back-href="{{ route('panel.users.index') }}">
  <x-slot name="heading">Import</x-slot>
  <x-slot name="meta">Excel yoki CSV fayldan foydalanuvchilar import qilish</x-slot>
</x-panel.page-header>


<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
  <div class="fade-up d1">
    <div class="p-card">
      <div class="p-card-title mb-1">Fayl yuklash</div>
      <div style="font-size:12px;color:var(--p-hint);margin-bottom:20px">
        Qo'llab-quvvatlanadigan formatlar: .xlsx, .xls, .csv
      </div>

      <form method="POST" action="{{ route('panel.users.import.post') }}" enctype="multipart/form-data">
        @csrf
        <div style="border:2px dashed var(--p-border);border-radius:10px;padding:30px;text-align:center;margin-bottom:20px;cursor:pointer"
             onclick="document.getElementById('importFile').click()">
          <i class="bi bi-cloud-upload" style="font-size:36px;color:var(--p-hint);display:block;margin-bottom:8px"></i>
          <div style="font-size:14px;font-weight:500;color:var(--p-text)">Fayl tanlash</div>
          <div style="font-size:12px;color:var(--p-hint);margin-top:4px">yoki bu yerga tashlang</div>
          <div id="fileNameDisplay" style="margin-top:10px;font-size:12px;color:var(--p-accent)"></div>
        </div>
        <input type="file" id="importFile" name="file"
               accept=".xlsx,.xls,.csv" style="display:none"
               onchange="document.getElementById('fileNameDisplay').textContent = this.files[0]?.name || ''">

        @error('file')
        <div style="font-size:12px;color:var(--p-danger);margin-bottom:12px">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn-p primary" style="width:100%">
          <i class="bi bi-upload"></i> Import qilish
        </button>
      </form>
    </div>
  </div>

  <div class="fade-up d2">
    <div class="p-card">
      <div class="p-card-title mb-3">Fayl formati</div>
      <div style="font-size:13px;color:var(--p-muted);margin-bottom:14px">
        Excel faylning birinchi qatorida quyidagi sarlavhalar bo'lishi kerak:
      </div>
      <div class="table-responsive kc-twrap">
        <table class="p-table">
          <thead>
            <tr><th>Ustun nomi</th><th>Ma'lumot turi</th><th>Majburiy</th></tr>
          </thead>
          <tbody>
            @foreach([
              ['ism','Matn','Ha'],
              ['familiya','Matn','Yo\'q'],
              ['telefon','Matn (+998...)','Ha'],
              ['email','Email','Yo\'q'],
              ['premium','Ha / Yo\'q','Yo\'q'],
              ['tasdiqlangan','Ha / Yo\'q','Yo\'q'],
              ['balans_uzs','Son','Yo\'q'],
            ] as $col)
            <tr>
              <td><code style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--p-accent)">{{ $col[0] }}</code></td>
              <td style="font-size:12px">{{ $col[1] }}</td>
              <td>
                @if($col[2] === 'Ha')
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

      <div style="margin-top:16px;padding:12px;background:var(--p-elevated);border-radius:8px">
        <div style="font-size:12px;font-weight:500;color:var(--p-text);margin-bottom:6px">
          <i class="bi bi-info-circle mr-1" style="color:var(--p-accent)"></i> Muhim eslatmalar:
        </div>
        <ul style="font-size:12px;color:var(--p-muted);margin:0;padding-left:16px;line-height:1.8">
          <li>Mavjud telefon raqamlar o'tkazib yuboriladi</li>
          <li>Yangi foydalanuvchilarga standart parol: <code style="font-family:'JetBrains Mono',monospace">12345678</code></li>
          <li>Maksimal fayl hajmi: 10MB</li>
        </ul>
      </div>

      <a href="#" class="btn-p ghost" style="width:100%;justify-content:center;margin-top:12px">
        <i class="bi bi-download"></i> Namuna fayl yuklash
      </a>
    </div>
  </div>
</div>

@endsection