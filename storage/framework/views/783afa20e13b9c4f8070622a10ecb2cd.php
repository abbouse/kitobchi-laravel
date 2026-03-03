<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'default',
    'rows' => [],
    'headRows' => [],
    'footRows' => [],
    'columns' => [],
    'headAttributes',
    'bodyAttributes',
    'footAttributes',
    'asyncUrl',
    'async' => false,
    'simple' => false,
    'notfound' => false,
    'creatable' => false,
    'reindex' => false,
    'reorderable' => false,
    'searchable' => false,
    'sticky' => false,
    'lazy' => false,
    'columnSelection' => false,
    'searchValue' => '',
    'translates' => [],
    'topLeft' => null,
    'topRight' => null,
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
    'name' => 'default',
    'rows' => [],
    'headRows' => [],
    'footRows' => [],
    'columns' => [],
    'headAttributes',
    'bodyAttributes',
    'footAttributes',
    'asyncUrl',
    'async' => false,
    'simple' => false,
    'notfound' => false,
    'creatable' => false,
    'reindex' => false,
    'reorderable' => false,
    'searchable' => false,
    'sticky' => false,
    'lazy' => false,
    'columnSelection' => false,
    'searchValue' => '',
    'translates' => [],
    'topLeft' => null,
    'topRight' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div
    class="js-table-builder-container"
    <?php if($async && $lazy): ?> data-lazy="<?php echo e("table_updated:$name"); ?>" <?php endif; ?>
>
    <div x-data="tableBuilder(
    <?php echo e((int) $creatable); ?>,
    <?php echo e((int) $reorderable); ?>,
    <?php echo e((int) $reindex); ?>,
    <?php echo e((int) $async); ?>,
    '<?php echo e($asyncUrl); ?>'
)"
        <?php echo MoonShine\Support\AlpineJs::eventBlade('table_row_added', $name, 'add(true)'); ?>
        <?php echo MoonShine\Support\AlpineJs::eventBlade('table_reindex', $name, 'resolveReindex'); ?>
        <?php echo MoonShine\Support\AlpineJs::eventBladeWhen($async, 'table_updated', $name, 'asyncRequest'); ?>
        <?php echo e($attributes); ?>

    >
        <?php if (isset($component)) { $__componentOriginalc947d7428c76dc25ba1504c5577c4f49 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc947d7428c76dc25ba1504c5577c4f49 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.iterable-wrapper','data' => ['searchable' => $async && $searchable,'searchPlaceholder' => $translates['search'],'searchValue' => $searchValue,'searchUrl' => $asyncUrl]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::iterable-wrapper'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($async && $searchable),'search-placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($translates['search']),'search-value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($searchValue),'search-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asyncUrl)]); ?>
             <?php $__env->slot('topLeft', null, []); ?> 
                <?php echo $topLeft ?? ''; ?>

             <?php $__env->endSlot(); ?>

             <?php $__env->slot('topRight', null, []); ?> 
                <?php echo $topRight ?? ''; ?>

             <?php $__env->endSlot(); ?>

            <?php if (isset($component)) { $__componentOriginal1f92ca05a1de5ca8db89fe2ff7983871 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f92ca05a1de5ca8db89fe2ff7983871 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'moonshine::components.table.index','data' => ['simple' => $simple,'notfound' => $notfound,'attributes' => $attributes,'headAttributes' => $headAttributes,'bodyAttributes' => $bodyAttributes,'footAttributes' => $footAttributes,'creatable' => $creatable,'sticky' => $sticky,'translates' => $translates,'dataName' => ''.e($name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['simple' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($simple),'notfound' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notfound),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'headAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($headAttributes),'bodyAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodyAttributes),'footAttributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($footAttributes),'creatable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($creatable),'sticky' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sticky),'translates' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($translates),'data-name' => ''.e($name).'']); ?>
                <?php if($headRows->isNotEmpty()): ?>
                     <?php $__env->slot('thead', null, []); ?> 
                        <?php $__currentLoopData = $headRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $row; ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                     <?php $__env->endSlot(); ?>
                <?php endif; ?>

                <?php if($rows->isNotEmpty()): ?>
                     <?php $__env->slot('tbody', null, []); ?> 
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $row; ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                     <?php $__env->endSlot(); ?>
                <?php endif; ?>

                <?php if($footRows->isNotEmpty()): ?>
                     <?php $__env->slot('tfoot', null, []); ?> 
                        <?php $__currentLoopData = $footRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $row; ?>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                     <?php $__env->endSlot(); ?>
                <?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1f92ca05a1de5ca8db89fe2ff7983871)): ?>
<?php $attributes = $__attributesOriginal1f92ca05a1de5ca8db89fe2ff7983871; ?>
<?php unset($__attributesOriginal1f92ca05a1de5ca8db89fe2ff7983871); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1f92ca05a1de5ca8db89fe2ff7983871)): ?>
<?php $component = $__componentOriginal1f92ca05a1de5ca8db89fe2ff7983871; ?>
<?php unset($__componentOriginal1f92ca05a1de5ca8db89fe2ff7983871); ?>
<?php endif; ?>

            <?php if($creatable): ?>
                <?php if (isset($component)) { $__componentOriginal7bab788aed2a0e8938609bb9914a586d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7bab788aed2a0e8938609bb9914a586d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Layout\Divider::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::layout.divider'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Layout\Divider::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7bab788aed2a0e8938609bb9914a586d)): ?>
<?php $attributes = $__attributesOriginal7bab788aed2a0e8938609bb9914a586d; ?>
<?php unset($__attributesOriginal7bab788aed2a0e8938609bb9914a586d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7bab788aed2a0e8938609bb9914a586d)): ?>
<?php $component = $__componentOriginal7bab788aed2a0e8938609bb9914a586d; ?>
<?php unset($__componentOriginal7bab788aed2a0e8938609bb9914a586d); ?>
<?php endif; ?>

                <?php echo $createButton; ?>

            <?php endif; ?>

            <?php if($hasPaginator): ?>
                <?php echo $paginator; ?>

            <?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc947d7428c76dc25ba1504c5577c4f49)): ?>
<?php $attributes = $__attributesOriginalc947d7428c76dc25ba1504c5577c4f49; ?>
<?php unset($__attributesOriginalc947d7428c76dc25ba1504c5577c4f49); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc947d7428c76dc25ba1504c5577c4f49)): ?>
<?php $component = $__componentOriginalc947d7428c76dc25ba1504c5577c4f49; ?>
<?php unset($__componentOriginalc947d7428c76dc25ba1504c5577c4f49); ?>
<?php endif; ?>
    </div>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/table/builder.blade.php ENDPATH**/ ?>