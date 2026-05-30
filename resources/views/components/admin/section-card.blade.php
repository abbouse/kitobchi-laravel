@props([
  'title' => null,
  'meta' => null,
])

<section {{ $attributes->merge(['class' => 'card-panel a122-section-card overflow-hidden']) }}>
  @if($title || $meta)
    <div class="card-panel-header d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        @if($title)
          <h2 class="card-panel-title">{{ $title }}</h2>
        @endif
        @if($meta)
          <div class="card-panel-sub" style="max-width:38rem;line-height:1.7;">{{ $meta }}</div>
        @endif
      </div>
      @if(isset($actions) && trim($actions) !== '')
        <div class="d-flex flex-wrap gap-2">
          {{ $actions }}
        </div>
      @endif
    </div>
  @endif
  <div class="px-4 px-xl-5 py-4 py-xl-4">
    {{ $slot }}
  </div>
</section>
