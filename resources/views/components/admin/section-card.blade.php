@props([
  'title' => null,
  'meta' => null,
])

<section {{ $attributes->merge(['class' => 'card border-0 shadow-sm rounded-4']) }}>
  @if($title || $meta)
    <div class="card-header bg-white border-bottom-0 px-4 pt-4 pb-0 d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        @if($title)
          <h2 class="h5 mb-1 text-dark fw-semibold">{{ $title }}</h2>
        @endif
        @if($meta)
          <div class="small text-secondary">{{ $meta }}</div>
        @endif
      </div>
      @if(isset($actions) && trim($actions) !== '')
        <div class="d-flex flex-wrap gap-2">
          {{ $actions }}
        </div>
      @endif
    </div>
  @endif
  <div class="card-body px-4 py-4">
    {{ $slot }}
  </div>
</section>
