<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => '',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Orqaga',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => '',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Orqaga',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div <?php echo e($attributes->class(['p-page-header fade-up'])); ?>>
    <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
        <?php if($backHref): ?>
            <a href="<?php echo e($backHref); ?>"
               class="btn-p ghost icon mt-0.5 shrink-0 sm:mt-1"
               title="<?php echo e($backLabel); ?>"
               aria-label="<?php echo e($backLabel); ?>">
                <i class="bi bi-arrow-left"></i>
            </a>
        <?php endif; ?>
        <div class="min-w-0">
            <?php if(isset($heading)): ?>
                <?php
                    $__hk = (string) $heading;
                ?>
                <?php if(str_contains($__hk, '<')): ?>
                    <div class="min-w-0 text-gray-800 dark:text-white/90 [&_.page-title]:text-xl [&_.page-title]:font-semibold [&_.page-title]:tracking-tight [&_.page-title]:text-gray-800 sm:[&_.page-title]:text-2xl xl:[&_.page-title]:text-[1.7rem] xl:[&_.page-title]:leading-snug 2xl:[&_.page-title]:text-[1.85rem] dark:[&_.page-title]:text-white/90">
                        <?php echo $heading; ?>

                    </div>
                <?php else: ?>
                    <h1 class="text-xl font-semibold tracking-tight text-gray-800 sm:text-2xl xl:text-[1.7rem] xl:leading-snug 2xl:text-[1.85rem] dark:text-white/90"><?php echo e($heading); ?></h1>
                <?php endif; ?>
            <?php elseif($title !== ''): ?>
                <h1 class="text-xl font-semibold tracking-tight text-gray-800 sm:text-2xl xl:text-[1.7rem] xl:leading-snug 2xl:text-[1.85rem] dark:text-white/90"><?php echo e($title); ?></h1>
            <?php endif; ?>
            <?php if(isset($meta)): ?>
                <?php
                    $__mk = (string) $meta;
                ?>
                <?php if(str_contains($__mk, '<')): ?>
                    <div class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed [&_strong]:text-gray-700 dark:[&_strong]:text-white/80 [&_.page-sub]:mt-0">
                        <?php echo $meta; ?>

                    </div>
                <?php else: ?>
                    <p class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed"><?php echo e($meta); ?></p>
                <?php endif; ?>
            <?php elseif($subtitle): ?>
                <p class="mt-1.5 max-w-prose text-sm leading-relaxed text-gray-500 dark:text-gray-400 xl:mt-2 xl:max-w-[62ch] xl:text-base xl:leading-relaxed"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if(isset($actions)): ?>
        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end"><?php echo e($actions); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/components/panel/page-header.blade.php ENDPATH**/ ?>