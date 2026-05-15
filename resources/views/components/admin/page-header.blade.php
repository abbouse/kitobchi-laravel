@props([
  'eyebrow' => null,
  'title',
  'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'kc-page-header']) }}>
  <div class="row g-4 align-items-start">
    <div class="col-xl-8">
      @if($eyebrow)
        <div class="kc-page-header__eyebrow">{{ $eyebrow }}</div>
      @endif
      <h1 class="kc-page-header__title">{{ $title }}</h1>
      @if($subtitle)
        <p class="kc-page-header__subtitle">{{ $subtitle }}</p>
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
