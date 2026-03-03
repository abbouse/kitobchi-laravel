<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'simple' => false,
    'async' => false,
    'has_pages' => false,
    'current_page' => 0,
    'last_page' => 0,
    'per_page' => 0,
    'first_page_url' => '',
    'next_page_url' => '',
    'prev_page_url' => '',
    'to' => 0,
    'from' => 0,
    'total' => 0,
    'links' => [],
    'translates' => [],
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
    'simple' => false,
    'async' => false,
    'has_pages' => false,
    'current_page' => 0,
    'last_page' => 0,
    'per_page' => 0,
    'first_page_url' => '',
    'next_page_url' => '',
    'prev_page_url' => '',
    'to' => 0,
    'from' => 0,
    'total' => 0,
    'links' => [],
    'translates' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php if($simple): ?>
    <ul class="pagination-list simple">
        
        <li>
            <?php if($prev_page_url): ?>
                <a href="<?php echo e($prev_page_url); ?>"
                   <?php if($async): ?> @click.prevent="asyncRequest" <?php endif; ?>
                   class="pagination-simple"
                >
                    <?php echo $translates['previous']; ?>

                </a>
            <?php else: ?>
                <span class="pagination-simple disabled">
                    <?php echo $translates['previous']; ?>

                </span>
            <?php endif; ?>
        </li>

        
        <li>
            <?php if($next_page_url): ?>
                <a href="<?php echo e($next_page_url); ?>"
                   <?php if($async): ?> @click.prevent="asyncRequest" <?php endif; ?>
                   class="pagination-simple"
                >
                    <?php echo $translates['next']; ?>

                </a>
            <?php else: ?>
                <span class="pagination-simple disabled">
                    <?php echo $translates['next']; ?>

                </span>
            <?php endif; ?>
        </li>
    </ul>
<?php elseif($has_pages): ?>
    <!-- Pagination -->
    <div class="pagination">
        <ul class="pagination-list">
            <?php if($current_page > 1): ?>
                <li class="pagination-item">
                    <a href="<?php echo e($prev_page_url); ?>"
                       <?php if($async): ?> @click.prevent="asyncRequest" <?php endif; ?>
                       class="pagination-first"
                       title="<?php echo $translates['previous']; ?>"
                    >
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'chevron-double-left','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $attributes = $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $component = $__componentOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                
                <?php if(is_string($link)): ?>
                    <li class="pagination-item">
                        <span class="pagination-dots"><?php echo e($link); ?></span>
                    </li>
                <?php endif; ?>

                <?php if($link['url']): ?>
                <li class="pagination-item">
                    <a href="<?php echo e($link['url']); ?>"
                       <?php if($async): ?> @click.prevent="asyncRequest" <?php endif; ?>
                       class="pagination-page
                       <?php if($link['active']): ?> _is-active <?php endif; ?>"
                    >
                        <?php echo $link['label']; ?>

                    </a>
                </li>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($current_page < $last_page): ?>
                <li class="pagination-item">
                    <a href="<?php echo e($next_page_url); ?>"
                       <?php if($async): ?> @click.prevent="asyncRequest" <?php endif; ?>
                       class="pagination-last"
                       title="<?php echo $translates['next']; ?>"
                    >
                        <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'chevron-double-right','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $attributes = $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $component = $__componentOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <div class="pagination-results">
            <?php echo $translates['showing']; ?>

            <?php if($from): ?>
                <?php echo e($from); ?>

                <?php echo $translates['to']; ?>

                <?php echo e($to); ?>

            <?php else: ?>
                <?php echo e($per_page); ?>

            <?php endif; ?>
            <?php echo $translates['of']; ?>

            <?php echo e($total); ?>

            <?php echo $translates['results']; ?>

        </div>
    </div>
    <!-- END: Pagination -->
<?php endif; ?>

<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/components/pagination.blade.php ENDPATH**/ ?>