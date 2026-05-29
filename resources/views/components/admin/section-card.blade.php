@props([
  'title' => null,
  'meta' => null,
])

<section {{ $attributes->merge(['class' => 'card border-0 shadow-sm rounded-5 overflow-hidden']) }} style="background:linear-gradient(180deg, rgba(255,255,255,0.99), rgba(249,250,253,0.98));">
  @if($title || $meta)
    <div class="card-header bg-transparent border-bottom-0 px-4 px-xl-5 pt-4 pt-xl-5 pb-0 d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        @if($title)
          <h2 class="h5 mb-1 text-dark fw-semibold" style="letter-spacing:-0.02em;">{{ $title }}</h2>
        @endif
        @if($meta)
          <div class="small text-secondary" style="max-width:38rem;line-height:1.7;">{{ $meta }}</div>
        @endif
      </div>
      @if(isset($actions) && trim($actions) !== '')
        <div class="d-flex flex-wrap gap-2">
          {{ $actions }}
        </div>
      @endif
    </div>
  @endif
  <div class="card-body px-4 px-xl-5 py-4 py-xl-4">
    {{ $slot }}
  </div>
</section>
