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

<div <?php echo e($attributes->class(['page-head card-panel p-4 mb-4'])); ?>>
    <div class="d-flex min-w-0 flex-grow-1 align-items-start gap-3">
        <?php if($backHref): ?>
            <a href="<?php echo e($backHref); ?>"
               class="btn btn-outline-secondary btn-sm flex-shrink-0"
               title="<?php echo e($backLabel); ?>"
               aria-label="<?php echo e($backLabel); ?>">
                <i class="bi bi-arrow-left"></i>
            </a>
        <?php endif; ?>
        <div class="min-w-0">
            <?php if(isset($heading)): ?>
                <?php ($__headingMarkup = (string) $heading); ?>
                <?php if(str_contains($__headingMarkup, '<')): ?>
                    <div class="min-w-0">
                        <?php echo $heading; ?>

                    </div>
                <?php else: ?>
                    <h1 class="page-title"><?php echo e($heading); ?></h1>
                <?php endif; ?>
            <?php elseif($title !== ''): ?>
                <h1 class="page-title"><?php echo e($title); ?></h1>
            <?php endif; ?>

            <?php if(isset($meta)): ?>
                <?php ($__metaMarkup = (string) $meta); ?>
                <?php if(str_contains($__metaMarkup, '<')): ?>
                    <div class="page-subtitle">
                        <?php echo $meta; ?>

                    </div>
                <?php else: ?>
                    <p class="page-subtitle"><?php echo e($meta); ?></p>
                <?php endif; ?>
            <?php elseif($subtitle): ?>
                <p class="page-subtitle"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($actions)): ?>
        <div class="d-flex flex-shrink-0 flex-wrap align-items-center gap-2 justify-content-end"><?php echo e($actions); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/components/a122/page-header.blade.php ENDPATH**/ ?>