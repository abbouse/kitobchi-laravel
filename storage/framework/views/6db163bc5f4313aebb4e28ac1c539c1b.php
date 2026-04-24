<?php
    $__seoTitle = __('legal.index.seo_title');
    $__seoDesc = __('legal.index.seo_desc');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
?>

<?php $__env->startSection('title', $__seoTitle); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('legal.index'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php
        $items = [];
        $pos = 1;
        foreach ($policies as $p) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $p->localizedTitle(),
                'url' => route('legal.policy', $p->slug),
            ];
        }
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => route('legal.index').'#webpage',
                    'url' => route('legal.index'),
                    'name' => $__seoTitle,
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                ],
                [
                    '@type' => 'ItemList',
                    'name' => __('legal.index.ld_list_name'),
                    'numberOfItems' => count($items),
                    'itemListElement' => $items,
                ],
            ],
        ];
    ?>
    <script type="application/ld+json"><?php echo json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <section class="section">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-legal">
                    <h1 class="section-heading"><?php echo e(__('legal.index.heading')); ?></h1>
                    <p class="subheading"><?php echo e(__('legal.index.intro')); ?></p>
                </div>

                <?php if($policies->isEmpty()): ?>
                    <div class="kc-legal-empty">
                        <div class="kc-legal-doc-icon kc-legal-doc-icon--muted" aria-hidden="true">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p class="subheading cc-features"><?php echo e(__('legal.index.empty')); ?></p>
                    </div>
                <?php else: ?>
                    <div class="kc-legal-doc-grid">
                        <?php $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $s = strtolower($policy->slug);
                                if (str_contains($s, 'maxfiylik') || str_contains($s, 'privacy')) {
                                    $icon = 'shield';
                                } elseif (str_contains($s, 'obuna') || str_contains($s, 'subscription')) {
                                    $icon = 'stack';
                                } elseif (str_contains($s, 'shart') || str_contains($s, 'terms') || str_contains($s, 'foydalanish')) {
                                    $icon = 'list';
                                } else {
                                    $icon = 'doc';
                                }
                            ?>
                            <a href="<?php echo e(route('legal.policy', $policy->slug)); ?>" class="kc-legal-doc-card w-inline-block">
                                <span class="kc-legal-doc-card__icon" aria-hidden="true">
                                    <?php switch($icon):
                                        case ('shield'): ?>
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php break; ?>
                                        <?php case ('stack'): ?>
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 3 2 8l10 5 10-5-10-5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="m2 13 10 5 10-5M2 18l10 5 10-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php break; ?>
                                        <?php case ('list'): ?>
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <?php break; ?>
                                        <?php default: ?>
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                    <?php endswitch; ?>
                                </span>
                                <span class="kc-legal-doc-card__main">
                                    <span class="kc-legal-doc-card__title"><?php echo e($policy->localizedTitle()); ?></span>
                                    <span class="kc-legal-doc-card__meta"><?php echo e(__('legal.index.updated')); ?> <?php echo e($policy->updated_at->format('d.m.Y')); ?></span>
                                </span>
                                <span class="kc-legal-doc-card__chev" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('legal.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/legal/index.blade.php ENDPATH**/ ?>