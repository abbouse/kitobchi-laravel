@props([
    'title' => '',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Orqaga',
])

<div {{ $attributes->class(['page-head card-panel p-4 mb-4']) }}>
    <div class="d-flex min-w-0 flex-grow-1 align-items-start gap-3">
        @if($backHref)
            <a href="{{ $backHref }}"
               class="btn btn-outline-secondary btn-sm flex-shrink-0"
               title="{{ $backLabel }}"
               aria-label="{{ $backLabel }}">
                <i class="bi bi-arrow-left"></i>
            </a>
        @endif
        <div class="min-w-0">
            @isset($heading)
                @php($__headingMarkup = (string) $heading)
                @if(str_contains($__headingMarkup, '<'))
                    <div class="min-w-0">
                        {!! $heading !!}
                    </div>
                @else
                    <h1 class="page-title">{{ $heading }}</h1>
                @endif
            @elseif($title !== '')
                <h1 class="page-title">{{ $title }}</h1>
            @endif

            @isset($meta)
                @php($__metaMarkup = (string) $meta)
                @if(str_contains($__metaMarkup, '<'))
                    <div class="page-subtitle">
                        {!! $meta !!}
                    </div>
                @else
                    <p class="page-subtitle">{{ $meta }}</p>
                @endif
            @elseif($subtitle)
                <p class="page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="d-flex flex-shrink-0 flex-wrap align-items-center gap-2 justify-content-end">{{ $actions }}</div>
    @endisset
</div>
