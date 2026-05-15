@props([
  'title' => null,
  'meta' => null,
])

<section {{ $attributes->merge(['class' => 'kc-panel']) }}>
  @if($title || $meta)
    <div class="kc-panel__header d-flex flex-wrap align-items-start justify-content-between gap-3">
      <div>
        @if($title)
          <h2 class="kc-panel__title">{{ $title }}</h2>
        @endif
        @if($meta)
          <div class="kc-panel__meta">{{ $meta }}</div>
        @endif
      </div>
      @if(isset($actions) && trim($actions) !== '')
        <div class="d-flex flex-wrap gap-2">
          {{ $actions }}
        </div>
      @endif
    </div>
  @endif
  <div class="kc-panel__body">
    {{ $slot }}
  </div>
</section>
