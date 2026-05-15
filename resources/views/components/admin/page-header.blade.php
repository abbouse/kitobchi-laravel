@props([
  'eyebrow' => null,
  'title',
  'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'py-1']) }}>
  <div class="row g-4 align-items-start">
    <div class="col-xl-8">
      @if($eyebrow)
        <div class="text-uppercase small fw-semibold text-secondary mb-2" style="letter-spacing:.14em;">{{ $eyebrow }}</div>
      @endif
      <h1 class="display-6 fw-bold text-dark mb-2">{{ $title }}</h1>
      @if($subtitle)
        <p class="mb-0 text-secondary fs-6">{{ $subtitle }}</p>
      @endif
    </div>
    @if(trim($slot) !== '')
      <div class="col-xl-4">
        <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
          {{ $slot }}
        </div>
      </div>
    @endif
  </div>
</section>
