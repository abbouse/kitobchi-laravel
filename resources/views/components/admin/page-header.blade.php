@props([
  'eyebrow' => null,
  'title',
  'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'page-head card-panel p-4 mb-4']) }}>
  <div class="d-flex min-w-0 flex-grow-1 align-items-start gap-3">
    <div class="min-w-0">
      <h1 class="page-title">{{ $title }}</h1>
      @if($subtitle)
        <p class="page-subtitle">{{ $subtitle }}</p>
      @endif
    </div>
  </div>
  @if(trim($slot) !== '')
    <div class="d-flex flex-shrink-0 flex-wrap align-items-center gap-2 justify-content-end">
      {{ $slot }}
    </div>
  @endif
</section>
