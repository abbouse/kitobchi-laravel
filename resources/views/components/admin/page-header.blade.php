@props([
  'eyebrow' => null,
  'title',
  'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'kc-page-hero']) }}>
  <div class="row g-4 align-items-start">
    <div class="col-xl-8">
      @if($eyebrow)
        <div class="kc-page-hero__eyebrow">{{ $eyebrow }}</div>
      @endif
      <h1 class="kc-page-hero__title">{{ $title }}</h1>
      @if($subtitle)
        <p class="kc-page-hero__subtitle">{{ $subtitle }}</p>
      @endif
    </div>
    @if(trim($slot) !== '')
      <div class="col-xl-4">
        <div class="kc-page-hero__actions">
          {{ $slot }}
        </div>
      </div>
    @endif
  </div>
</section>
