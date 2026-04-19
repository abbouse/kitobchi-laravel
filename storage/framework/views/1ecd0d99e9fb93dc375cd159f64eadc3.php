<?php
    $__seoTitle = $policy->localizedTitle().__('legal.show.seo_suffix');
    $__seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($policy->localizedContent()))), 160, '…');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
?>

<?php $__env->startSection('title', $__seoTitle); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('legal.policy', $policy->slug),
        'ogType' => 'article',
        'articleModified' => $policy->updated_at?->toIso8601String(),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    '@id' => route('legal.policy', $policy->slug).'#webpage',
                    'url' => route('legal.policy', $policy->slug),
                    'name' => $__seoTitle,
                    'description' => $__seoDesc,
                    'inLanguage' => $__inLang,
                    'dateModified' => $policy->updated_at?->toIso8601String(),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => __('legal.show.breadcrumb_home'),
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => __('legal.show.breadcrumb_legal'),
                            'item' => route('legal.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $policy->localizedTitle(),
                            'item' => route('legal.policy', $policy->slug),
                        ],
                    ],
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
                    <h1 class="section-heading"><?php echo e($policy->localizedTitle()); ?></h1>
                    <p class="subheading"><?php echo e(__('legal.show.updated')); ?> <?php echo e($policy->updated_at->format('d.m.Y')); ?></p>
                </div>

                <div class="legal-rt w-richtext">
                    <?php echo $policy->localizedContent(); ?>

                </div>

                <div class="kc-legal-actions">
                    <a href="<?php echo e(route('legal.index')); ?>" class="cta w-inline-block">
                        <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                        <div class="cta-inner cc-dark">
                            <div><strong><?php echo e(__('legal.show.all_docs')); ?></strong></div>
                        </div>
                    </a>
                    <a href="#" class="cta w-inline-block" onclick="window.print(); return false;">
                        <div class="cta-bg u-rainbow u-blur-perf"></div>
                        <div class="cta-inner">
                            <div><strong><?php echo e(__('legal.show.print')); ?></strong></div>
                        </div>
                    </a>
                </div>

                <?php $otherPolicies = $policies->where('id', '!=', $policy->id)->take(4); ?>
                <?php if($otherPolicies->isNotEmpty()): ?>
                    <div class="section-header cc-legal kc-legal-more-head">
                        <h2 class="heading-m"><?php echo e(__('legal.show.other_heading')); ?></h2>
                    </div>
                    <div class="kc-legal-index-list">
                        <?php $__currentLoopData = $otherPolicies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $other): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('legal.policy', $other->slug)); ?>" class="kc-legal-index-row w-inline-block">
                                <span class="kc-legal-index-title"><?php echo e($other->localizedTitle()); ?></span>
                                <span class="kc-legal-index-meta"><?php echo e($other->updated_at->format('d.m.Y')); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('legal.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/legal/show.blade.php ENDPATH**/ ?>