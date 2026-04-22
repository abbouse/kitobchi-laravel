@props([
    'title' => '',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Orqaga',
])

<div {{ $attributes->class(['p-page-header fade-up']) }}>
    <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
        @if($backHref)
            <a href="{{ $backHref }}"
               class="btn-p ghost icon mt-0.5 shrink-0 sm:mt-1"
               title="{{ $backLabel }}"
               aria-label="{{ $backLabel }}">
                <i class="bi bi-arrow-left"></i>
            </a>
        @endif
        <div class="min-w-0">
            @isset($heading)
                @php($__headingMarkup = (string) $heading)
                @if(str_contains($__headingMarkup, '<'))
                    <div class="min-w-0 text-gray-800 dark:text-white/90 [&_.page-title]:text-xl [&_.page-title]:font-semibold [&_.page-title]:tracking-tight [&_.page-title]:text-gray-800 sm:[&_.page-title]:text-2xl xl:[&_.page-title]:text-[1.7rem] xl:[&_.page-title]:leading-snug 2xl:[&_.page-title]:text-[1.85rem] dark:[&_.page-title]:text-white/90">
                        {!! $heading !!}
                    </div>
                @else
                    <h1 class="text-xl font-semibold tracking-tight text-gray-800 sm:text-2xl xl:text-[1.7rem] xl:leading-snug 2xl:text-[1.85rem] dark:text-white/90">{{ $heading }}</h1>
                @endif
            @elseif($title !== '')
                <h1 class="text-xl font-semibold tracking-tight text-gray-800 sm:text-2xl xl:text-[1.7rem] xl:leading-snug 2xl:text-[1.85rem] dark:text-white/90">{{ $title }}</h1>
            @endif

            @isset($meta)
                @php($__metaMarkup = (string) $meta)
                @if(str_contains($__metaMarkup, '<'))
                    <div class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed [&_strong]:text-gray-700 dark:[&_strong]:text-white/80 [&_.page-sub]:mt-0">
                        {!! $meta !!}
                    </div>
                @else
                    <p class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed">{{ $meta }}</p>
                @endif
            @elseif($subtitle)
                <p class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">{{ $actions }}</div>
    @endisset
</div>
