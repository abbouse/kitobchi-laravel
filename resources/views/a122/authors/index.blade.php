@extends('a122.layouts.admin')
@section('title', 'Mualliflar')
@section('page-title', 'Mualliflar')
@section('page-eyebrow', 'Author directory')

@section('content')
<div class="d-flex flex-column gap-4">
  <x-admin.page-header
    eyebrow="Author directory"
    title="Mualliflar"
    subtitle="{{ $authors->total() }} ta yozuv">
    <form method="GET" class="kc-search flex-grow-1" style="max-width: 24rem;">
      <i class="bi bi-search kc-search__icon"></i>
      <input name="search" value="{{ request('search') }}" placeholder="Muallif qidirish..." class="form-control">
    </form>
    <form method="POST" action="{{ route('admin.authors.sync-book-uz') }}">
      @csrf
      <button class="btn-p ghost">
        <i class="bi bi-arrow-repeat"></i>
        <span>Book.uz sync</span>
      </button>
    </form>
    <form method="POST" action="{{ route('admin.authors.backfill-books') }}">
      @csrf
      <button class="btn-p ghost">
        <i class="bi bi-link-45deg"></i>
        <span>Eski kitoblarni ulash</span>
      </button>
    </form>
    <a href="{{ route('admin.authors.create') }}" class="btn-p primary">
      <i class="bi bi-plus-lg"></i>
      <span>Qo‘shish</span>
    </a>
  </x-admin.page-header>

  @if(session('success'))
    <div class="alert alert-success kc-flash mb-0">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger kc-flash mb-0">{{ session('error') }}</div>
  @endif

  <div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Jami muallif"
        :value="number_format($stats['total'])"
        icon="pen"
        tone="primary" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Rasmli kartalar"
        :value="number_format($stats['with_image'])"
        icon="image"
        tone="success" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="AI nomzodlar"
        :value="number_format($stats['ai_candidates'])"
        icon="stars"
        tone="warning" />
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <x-admin.stat-card
        label="Bog‘langan kitoblar"
        :value="number_format($stats['linked_books'])"
        icon="book"
        tone="info" />
    </div>
  </div>

  <x-admin.section-card title="Mualliflar jadvali" :meta="$authors->total() . ' ta author yozuvi topildi.'">
    <div class="kc-table-shell table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Preview</th>
            <th>Muallif</th>
            <th>Kitoblar</th>
            <th>Holat</th>
            <th>Manba</th>
            <th class="text-end">Amallar</th>
          </tr>
        </thead>
        <tbody>
          @forelse($authors as $author)
            <tr>
              <td class="small text-secondary">#{{ $author->id }}</td>
              <td>
                <div style="width:72px;height:72px;" class="rounded-4 overflow-hidden border bg-light d-flex align-items-center justify-content-center">
                  <img src="{{ $author->display_image_url }}" alt="{{ $author->name }}" class="w-100 h-100 object-fit-cover">
                </div>
              </td>
              <td>
                <div class="fw-semibold">{{ $author->name }}</div>
                <div class="small text-secondary mt-1">
                  @if($author->external_id)
                    Book.uz ID: {{ $author->external_id }}
                  @else
                    Manual author card
                  @endif
                </div>
              </td>
              <td>
                <span class="badge rounded-pill text-bg-light border">{{ number_format($author->books_count) }} ta</span>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  @if($author->image_url)
                    <span class="badge rounded-pill text-bg-success-subtle border border-success-subtle text-success-emphasis">Rasm bor</span>
                  @else
                    <span class="badge rounded-pill text-bg-light border">Default avatar</span>
                  @endif

                  @if($author->has_multiple_authors)
                    <span class="badge rounded-pill text-bg-secondary">Ko‘p muallif</span>
                  @elseif($author->needs_ai_portrait)
                    <span class="badge rounded-pill text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">AI mos</span>
                  @endif
                </div>
              </td>
              <td>
                @if($author->source_url)
                  <a href="{{ $author->source_url }}" target="_blank" rel="noopener" class="text-decoration-none">Source</a>
                @else
                  <span class="small text-secondary">—</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                  @if($author->needs_ai_portrait)
                    <form method="POST" action="{{ route('admin.authors.generate-image-prompt', $author) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-light border kc-table-action" title="AI prompt yaratish">
                        <i class="bi bi-stars"></i>
                      </button>
                    </form>
                  @endif
                  <a href="{{ route('admin.authors.edit', $author) }}" class="btn btn-sm btn-light border kc-table-action" title="Tahrirlash">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.authors.destroy', $author) }}" onsubmit="return confirm('Muallifni o\\'chirishni tasdiqlaysizmi?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-light border kc-table-action text-danger" title="O‘chirish">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-secondary">Hali mualliflar qo‘shilmagan.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin.section-card>

  <div>{{ $authors->links('a122.partials.pagination') }}</div>
</div>
@endsection
