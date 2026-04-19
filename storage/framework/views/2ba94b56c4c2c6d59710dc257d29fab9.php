<?php
    $codes = config('landing_locales.codes', ['uz']);
    $labels = config('landing_locales.labels', []);
    $current = app()->getLocale();
    $here = request()->getRequestUri();
?>
<details class="kc-lang">
    <summary class="kc-lang__summary" aria-label="<?php echo e(__('nav.lang_label')); ?>">
        <span class="kc-lang__surface">
            <span class="kc-lang__flag-wrap">
                <?php echo $__env->make('partials.flag-icon', ['code' => $current], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </span>
            <span class="kc-lang__label"><?php echo e($labels[$current] ?? strtoupper($current)); ?></span>
            <span class="kc-lang__chev" aria-hidden="true">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 4.25L6 7.75l3.5-3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </span>
    </summary>
    <div class="kc-lang__panel" role="listbox" aria-label="<?php echo e(__('nav.lang_label')); ?>">
        <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $href = route('locale.switch', ['locale' => $code, 'redirect' => $here]);
                $isActive = $current === $code;
            ?>
            <a href="<?php echo e($href); ?>"
               class="kc-lang__option <?php echo e($isActive ? 'kc-lang__option--active' : ''); ?>"
               <?php if($isActive): ?> aria-current="true" <?php endif; ?>
               role="option">
                <span class="kc-lang__option-flag"><?php echo $__env->make('partials.flag-icon', ['code' => $code], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <span class="kc-lang__option-text"><?php echo e($labels[$code] ?? strtoupper($code)); ?></span>
                <?php if($isActive): ?>
                    <span class="kc-lang__check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 8.25L6.5 11.25L12.5 4.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</details>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/partials/lang-switcher.blade.php ENDPATH**/ ?>