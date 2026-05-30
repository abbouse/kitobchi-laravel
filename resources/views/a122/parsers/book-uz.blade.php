@extends('a122.layouts.admin')
@section('title', 'book.uz Parser')
@section('page-title', 'book.uz Parser')

@php
  $selectedCategoryId = (string) request('category_id', old('category_id', ''));
@endphp

@section('content')
<div class="d-flex flex-column gap-4">
  @if(session('success') || session('warning') || session('parser_errors'))
    <section class="d-flex flex-column gap-3">
      @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
      @endif
      @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-0">{{ session('warning') }}</div>
      @endif
      @if(session('parser_errors'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">
          <div class="fw-semibold mb-2">Parser xatolari</div>
          <ul class="mb-0 ps-3">
            @foreach((array) session('parser_errors') as $parserError)
              <li>{{ $parserError }}</li>
            @endforeach
          </ul>
        </div>
      @endif
    </section>
  @endif

  <section class="card-panel parser-hero">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <span class="badge rounded-pill text-bg-dark">book.uz</span>
          <span class="badge rounded-pill text-bg-light">Seller #{{ $seller?->id ?? 55 }}</span>
          <span class="badge rounded-pill text-bg-light">{{ $seller?->shop_name ?? 'Seller topilmadi' }}</span>
          @if($stats['last_synced_at'])
            <span class="badge rounded-pill text-bg-light">So‘nggi sync: {{ \Illuminate\Support\Carbon::parse($stats['last_synced_at'])->format('d.m.Y H:i') }}</span>
          @endif
        </div>
        <h1 class="page-title mb-2">book.uz katalog parseri</h1>
        <p class="page-subtitle mb-0">Book.uz dagi barcha kitoblarni, stock yo‘qlari bilan birga ko‘rib chiqing. Rasmlar lokalga olinadi, mahsulotlar seller <strong>55</strong> nomidan bazaga qo‘shiladi. Parser sahifasi endi faqat bazadagi cache’ni ko‘rsatadi, haftalik sync alohida scheduler bilan yuradi.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.parsers.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-2"></i>Parserlar
        </a>
      </div>
    </div>
  </section>

  <div class="row g-3">
    <div class="col-6 col-xl-3">
      <div class="parser-kpi">
        <div class="parser-kpi__label">Jami yozuv</div>
        <div class="parser-kpi__value">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="parser-kpi">
        <div class="parser-kpi__label">Bizda mavjud</div>
        <div class="parser-kpi__value">{{ number_format($stats['existing']) }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="parser-kpi">
        <div class="parser-kpi__label">Stock bor</div>
        <div class="parser-kpi__value">{{ number_format($stats['in_stock']) }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="parser-kpi">
        <div class="parser-kpi__label">Stock yo‘q</div>
        <div class="parser-kpi__value">{{ number_format($stats['out_of_stock']) }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="parser-kpi">
        <div class="parser-kpi__label">Import qilingan</div>
        <div class="parser-kpi__value">{{ number_format($stats['imported']) }}</div>
      </div>
    </div>
  </div>

  <section class="card-panel">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
      <div>
        <h2 class="h5 mb-1">Katalogni yangilash</h2>
        <p class="text-secondary mb-0">To‘liq katalog yoki bitta link bo‘yicha sinov import. To‘liq weekly sync fonda yuradi, bu tugma esa admin qo‘lda yangilashi uchun qoldirilgan.</p>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.parsers.book-uz.sync') }}" class="row g-3 align-items-end">
      @csrf
      <div class="col-12 col-xl-4">
        <label class="form-label">Bitta mahsulot linki</label>
        <input type="url" name="source_url" class="form-control" placeholder="https://book.uz/books/details/...">
      </div>
      <div class="col-6 col-xl-2">
        <label class="form-label">Limit</label>
        <input type="number" name="limit" class="form-control" min="1" max="5000" placeholder="200">
      </div>
      <div class="col-6 col-xl-3">
        <label class="form-label">Birinchi yuklash limiti</label>
        <div class="small text-secondary">Sahifa o‘zi sync boshlamaydi. Ilk to‘ldirish uchun limit bilan yoki scheduler orqali yuklang.</div>
      </div>
      <div class="col-12 col-xl-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark flex-fill">
          <i class="bi bi-arrow-repeat me-2"></i>Katalogni yangilash
        </button>
      </div>
    </form>
  </section>

  <section class="card-panel">
    <form method="GET" class="row g-3 align-items-end mb-4">
      <div class="col-12 col-lg-4">
        <label class="form-label">Qidiruv</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nomi, muallif, ISBN...">
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label">Stock</label>
        <select name="stock" class="form-select">
          <option value="">Barchasi</option>
          <option value="in" @selected(request('stock') === 'in')>Bor</option>
          <option value="out" @selected(request('stock') === 'out')>Yo‘q</option>
        </select>
      </div>
      <div class="col-6 col-lg-2">
        <label class="form-label">Import</label>
        <select name="imported" class="form-select">
          <option value="">Barchasi</option>
          <option value="yes" @selected(request('imported') === 'yes')>Qilingan</option>
          <option value="no" @selected(request('imported') === 'no')>Qilinmagan</option>
        </select>
      </div>
      <div class="col-6 col-lg-2 d-flex gap-2">
        <button class="btn btn-outline-secondary flex-fill">
          <i class="bi bi-funnel me-2"></i>Filter
        </button>
      </div>
    </form>

    <form id="bulk-import-form" method="POST" action="{{ route('admin.parsers.book-uz.import-selected') }}">
      @csrf
    </form>
    <form id="bulk-import-stock-form" method="POST" action="{{ route('admin.parsers.book-uz.import-all-stock') }}">
      @csrf
      <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}">
    </form>

    <div>
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h2 class="h5 mb-1">Topilgan kitoblar</h2>
          <p class="text-secondary mb-0">Har bir kitob uchun barcha asosiy metadata, stock holati va rasm preview ko‘rinadi. Import paytida AI tavsiya qilgan kategoriya avtomatik ishlaydi, select esa faqat override uchun.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-end">
          <div class="parser-import-category">
            <label class="form-label mb-2">Kategoriya override</label>
            <select id="parser-import-category" name="category_id" class="form-select" form="bulk-import-form" onchange="syncParserCategoryOverride(this.value)">
              <option value="">AI tavsiya qilgan kategoriyani ishlatish</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($selectedCategoryId === (string) $category->id)>{{ $category->name_uz }}</option>
              @endforeach
            </select>
          </div>
          <button type="button" class="btn btn-outline-secondary" onclick="toggleAllParserRows(true)">Barchasini tanlash</button>
          <button type="button" class="btn btn-outline-secondary" onclick="toggleAllParserRows(false)">Tanlovni tozalash</button>
          <button type="submit" class="btn btn-success" form="bulk-import-stock-form">
            <i class="bi bi-lightning-charge me-2"></i>Stock borlarning hammasini qo‘shish
          </button>
          <button type="submit" class="btn btn-dark js-parser-import-submit" form="bulk-import-form">
            <i class="bi bi-download me-2"></i>Tanlanganlarni bazaga qo‘shish
          </button>
        </div>
      </div>

      <div class="row g-4">
        @if($items->count())
          @foreach($items as $item)
          <div class="col-12 col-xxl-6">
            <article class="parser-item-card h-100">
              <div class="parser-item-card__media">
                @if($item->primary_image_url)
                  <img src="{{ $item->primary_image_url }}" alt="{{ $item->title }}" class="parser-item-card__image">
                @else
                  <div class="parser-item-card__image parser-item-card__image--placeholder">
                    <i class="bi bi-book"></i>
                  </div>
                @endif
              </div>
              <div class="parser-item-card__body">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                  <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                      <span class="badge rounded-pill {{ $item->in_stock ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $item->in_stock ? 'STOCK BOR' : 'STOCK YO‘Q' }}
                      </span>
                      @if($item->imported_book_id)
                        <span class="badge rounded-pill text-bg-dark">BAZAGA QO‘SHILGAN</span>
                      @endif
                    </div>
                    <h3 class="h5 mb-1">{{ $item->title ?: 'Nomsiz' }}</h3>
                    <div class="text-secondary small">{{ $item->author ?: 'Muallif ko‘rsatilmagan' }}</div>
                    @if(data_get($item->payload, 'author_resolution.method'))
                      <div class="mt-1">
                        <span class="badge rounded-pill text-bg-light border">
                          Muallif: {{ strtoupper((string) data_get($item->payload, 'author_resolution.method')) }}
                        </span>
                      </div>
                    @endif
                  </div>
                  <label class="form-check mt-1">
                    <input class="form-check-input parser-item-checkbox" type="checkbox" name="item_ids[]" value="{{ $item->id }}" form="bulk-import-form">
                  </label>
                </div>

                <div class="parser-price-row mb-3">
                  <div class="parser-price-row__price">{{ number_format((int) ($item->price_uzs ?? 0), 0, ',', ' ') }} so‘m</div>
                  @if($item->rating_value)
                    <div class="parser-price-row__meta">
                      ⭐ {{ $item->rating_value }}
                      @if($item->rating_count !== null)
                        <span>· {{ $item->rating_count }} ta</span>
                      @endif
                    </div>
                  @endif
                </div>

                <div class="parser-meta-grid">
                  <div><span>ISBN</span><strong>{{ $item->isbn ?: '—' }}</strong></div>
                  <div><span>Kategoriya</span><strong>{{ $item->source_category ?: '—' }}</strong></div>
                  <div><span>AI kategoriya</span><strong>{{ $item->suggestedCategory?->name_uz ?: ($item->suggested_category_name ?: '—') }}</strong></div>
                  <div><span>AI usuli</span><strong>{{ $item->category_ai_payload['method'] ?? '—' }}</strong></div>
                  <div><span>Nashriyot</span><strong>{{ $item->publisher ?: '—' }}</strong></div>
                  <div><span>Tarjimon</span><strong>{{ $item->translator ?: '—' }}</strong></div>
                  <div><span>Til</span><strong>{{ $item->language ?: '—' }}</strong></div>
                  <div><span>Yozuv</span><strong>{{ $item->script ?: '—' }}</strong></div>
                  <div><span>Muqova</span><strong>{{ $item->cover_type ?: '—' }}</strong></div>
                  <div><span>Yil</span><strong>{{ $item->year ?: '—' }}</strong></div>
                  <div><span>Sahifa</span><strong>{{ $item->pages ?: '—' }}</strong></div>
                  <div><span>Seller</span><strong>#{{ $seller?->id ?? 55 }}</strong></div>
                </div>

                @if(!empty($item->suggested_tag_names))
                  <div class="parser-tag-section">
                    <div class="parser-tag-section__title">
                      AI teglar
                      <span>{{ $item->tags_ai_payload['method'] ?? '—' }}</span>
                    </div>
                    <div class="parser-tag-cloud">
                      @foreach($item->suggested_tag_names as $tagName)
                        <span class="parser-tag-pill">{{ $tagName }}</span>
                      @endforeach
                    </div>
                  </div>
                @endif

                @if($item->description)
                  <p class="parser-description">{{ \Illuminate\Support\Str::limit($item->description, 220) }}</p>
                @endif

                @if($item->last_import_error)
                  <div class="alert alert-danger border-0 rounded-4 py-2 px-3 mt-3 mb-0">
                    <div class="fw-semibold small mb-1">So‘nggi import xatosi</div>
                    <div class="small">{{ \Illuminate\Support\Str::limit($item->last_import_error, 260) }}</div>
                  </div>
                @endif

                @if($item->matchedBook || $item->importedBook)
                  <div class="parser-match-note">
                    <div class="parser-match-note__title">
                      {{ $item->importedBook ? 'Bazadagi kitob topildi' : 'Sellerda o‘xshash kitob topildi' }}
                    </div>
                    <div class="parser-match-note__body">
                      @php
                        $linkedBook = $item->importedBook ?: $item->matchedBook;
                      @endphp
                      <strong>#{{ $linkedBook?->id }}</strong>
                      {{ $linkedBook?->name }}
                      @if($linkedBook?->author)
                        · {{ $linkedBook->author }}
                      @endif
                      @if($linkedBook?->isbn)
                        · ISBN: {{ $linkedBook->isbn }}
                      @endif
                      @if($item->match_confidence)
                        · Match: {{ $item->match_confidence }}%
                      @endif
                      @if($item->match_reason)
                        · {{ $item->match_reason }}
                      @endif
                    </div>
                  </div>
                @endif

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-auto pt-3">
                  <div class="small text-secondary">
                    @if($item->importedBook)
                      Bazadagi mahsulot: <a href="{{ route('admin.books.show', $item->importedBook) }}">#{{ $item->importedBook->id }}</a>
                    @elseif($item->matchedBook)
                      Sellerda mavjud: <a href="{{ route('admin.books.show', $item->matchedBook) }}">#{{ $item->matchedBook->id }}</a>
                    @else
                      Hali bazaga qo‘shilmagan
                    @endif
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $item->source_url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-box-arrow-up-right me-1"></i>Manba
                    </a>
                    <form method="POST" action="{{ route('admin.parsers.book-uz.import-item', $item) }}">
                      @csrf
                      <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}" class="js-parser-category-hidden">
                      <button type="submit" class="btn btn-dark btn-sm js-parser-import-submit">
                        <i class="bi bi-download me-1"></i>{{ $item->imported_book_id ? 'Qayta import' : 'Bazaga qo‘shish' }}
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </article>
          </div>
          @endforeach
        @else
          <div class="col-12">
            <div class="empty-state p-5 text-center">
              <div class="mb-3 fs-2"><i class="bi bi-search"></i></div>
              <h3>Katalog topilmadi</h3>
              <p>Avval book.uz katalogini yangilang yoki filterlarni bo‘shating.</p>
            </div>
          </div>
        @endif
      </div>
    </div>
  </section>

  @if(method_exists($items, 'links'))
    <div>{{ $items->links('a122.partials.pagination') }}</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  function syncParserCategoryOverride(value) {
    const stockFormField = document.querySelector('#bulk-import-stock-form input[name="category_id"]');
    if (stockFormField) {
      stockFormField.value = value || '';
    }
  }
</script>
@endpush

@push('styles')
<style>
  .parser-hero,
  .parser-kpi,
  .parser-item-card {
    border-radius: 28px;
  }
  .parser-kpi {
    padding: 20px 22px;
    background: var(--template-card);
    border: 1px solid var(--template-border);
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.06);
  }
  .parser-kpi__label {
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--template-muted);
    margin-bottom: 6px;
  }
  .parser-kpi__value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--template-text);
  }
  .parser-item-card {
    display: grid;
    grid-template-columns: 168px minmax(0, 1fr);
    gap: 18px;
    padding: 18px;
    background: var(--template-card);
    border: 1px solid var(--template-border);
    box-shadow: 0 20px 54px rgba(15, 23, 42, 0.06);
    min-height: 100%;
  }
  .parser-item-card__media {
    min-width: 0;
  }
  .parser-item-card__image {
    width: 100%;
    aspect-ratio: 0.74;
    border-radius: 22px;
    object-fit: cover;
    background: color-mix(in srgb, var(--template-card) 82%, var(--template-bg));
    border: 1px solid var(--template-border);
  }
  .parser-item-card__image--placeholder {
    display: grid;
    place-items: center;
    color: var(--template-muted);
    font-size: 2rem;
  }
  .parser-item-card__body {
    min-width: 0;
    display: flex;
    flex-direction: column;
  }
  .parser-price-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  .parser-price-row__price {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--template-text);
  }
  .parser-price-row__meta {
    color: var(--template-muted);
    font-size: .92rem;
  }
  .parser-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 16px;
    padding: 14px 16px;
    border-radius: 20px;
    background: color-mix(in srgb, var(--template-card) 82%, var(--template-bg));
    border: 1px solid var(--template-border);
  }
  .parser-meta-grid span {
    display: block;
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--template-muted);
    margin-bottom: 3px;
  }
  .parser-meta-grid strong {
    display: block;
    color: var(--template-text);
    font-size: .95rem;
    font-weight: 700;
    word-break: break-word;
  }
  .parser-description {
    margin: 14px 0 0;
    color: var(--template-muted);
    line-height: 1.65;
  }
  .parser-tag-section {
    margin-top: 14px;
    padding: 14px 16px;
    border-radius: 18px;
    background: color-mix(in srgb, #f59e0b 10%, var(--template-card));
    border: 1px solid rgba(245, 158, 11, 0.18);
  }
  .parser-tag-section__title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #92400e;
    font-weight: 700;
  }
  .parser-tag-section__title span {
    color: #b45309;
    font-weight: 600;
  }
  .parser-tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .parser-tag-pill {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
    font-size: .9rem;
    font-weight: 600;
    border: 1px solid rgba(245, 158, 11, 0.14);
  }
  .parser-match-note {
    margin-top: 14px;
    padding: 14px 16px;
    border-radius: 18px;
    background: color-mix(in srgb, var(--template-card) 82%, var(--template-bg));
    border: 1px solid var(--template-border);
  }
  .parser-match-note__title {
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--template-muted);
    margin-bottom: 6px;
  }
  .parser-match-note__body {
    color: var(--template-text);
    line-height: 1.6;
    font-size: .95rem;
  }
  .parser-import-category {
    min-width: 260px;
  }
  @media (max-width: 991.98px) {
    .parser-item-card {
      grid-template-columns: 1fr;
    }
    .parser-item-card__image {
      max-width: 220px;
    }
    .parser-import-category {
      min-width: 100%;
    }
  }
</style>
@endpush

@push('scripts')
<script>
  function toggleAllParserRows(checked) {
    document.querySelectorAll('.parser-item-checkbox').forEach((node) => {
      node.checked = checked;
    });
  }

  (function () {
    const categorySelect = document.getElementById('parser-import-category');
    if (!categorySelect) return;

    const syncImportState = () => {
      const value = categorySelect.value || '';

      document.querySelectorAll('.js-parser-category-hidden').forEach((input) => {
        input.value = value;
      });
    };

    categorySelect.addEventListener('change', syncImportState);
    syncImportState();
  })();
</script>
@endpush
