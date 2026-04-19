<?php $__env->startSection('main_classes', 'cc-careers'); ?>

<?php
    $__seoTitle = __('careers.seo.title');
    $__seoDesc = __('careers.seo.desc');
    $__inLang = config('landing_locales.bcp47.'.app()->getLocale(), 'uz-UZ');
?>

<?php $__env->startSection('title', $__seoTitle); ?>

<?php $__env->startPush('meta'); ?>
    <?php echo $__env->make('partials.seo-social', [
        'title' => $__seoTitle,
        'description' => $__seoDesc,
        'canonical' => route('careers.index'),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php
        $base = rtrim((string) (config('app.url') ?: url('/')), '/');
        $orgId = $base.'/#organization';
        $logoUrl = url('/images/logo/logo_blue.png');
        $org = [
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => 'Kitobchi',
            'url' => $base.'/',
            'logo' => ['@type' => 'ImageObject', 'url' => $logoUrl],
        ];
        $graph = [
            $org,
            [
                '@type' => 'WebPage',
                '@id' => route('careers.index').'#webpage',
                'url' => route('careers.index'),
                'name' => $__seoTitle,
                'description' => $__seoDesc,
                'inLanguage' => $__inLang,
            ],
        ];
        foreach ($vacancies as $v) {
            $placeName = $v->localizedLocation() ?: __('careers.ld.default_place');
            $loc = ['@type' => 'Place', 'name' => $placeName];
            $graph[] = [
                '@type' => 'JobPosting',
                '@id' => route('careers.index').'#job-'.$v->id,
                'title' => $v->localizedTitle(),
                'description' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($v->localizedDescription()))), 8000),
                'datePosted' => $v->created_at?->toIso8601String(),
                'hiringOrganization' => ['@id' => $orgId],
                'jobLocation' => $loc,
                'employmentType' => 'https://schema.org/FullTime',
                'directApply' => true,
                'url' => route('careers.index'),
            ];
        }
        $ld = ['@context' => 'https://schema.org', '@graph' => $graph];
    ?>
    <script type="application/ld+json"><?php echo json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="hero cc-careers">
        <div class="page-padding">
            <div class="container">
                <div class="home-hero-header">
                    <div class="eyebrow-pill">
                        <div class="eyebrow-pill-inner"><div><?php echo e(__('careers.hero.eyebrow')); ?></div></div>
                        <div class="eyebrow-pill-bg u-rainbow u-blur-perf"></div>
                    </div>
                    <h1 class="section-heading cc-careers-hero"><?php echo e(__('careers.hero.heading_l1')); ?><br><span class="u-weight-400"><?php echo e(__('careers.hero.heading_l2')); ?></span> <?php echo e(__('careers.hero.heading_l3')); ?></h1>
                    <p class="subheading cc-careers"><?php echo e(__('careers.hero.p1')); ?></p>
                    <p class="subheading cc-careers cc-last"><?php echo e(__('careers.hero.p2')); ?></p>
                    <div class="btn-wrapper cc-hero">
                        <a href="#open-roles" class="cta w-inline-block">
                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                            <div class="cta-inner">
                                <div><strong><?php echo e(__('careers.hero.open_roles')); ?></strong></div>
                            </div>
                        </a>
                        <a href="#open-inquiry" class="cta w-inline-block">
                            <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                            <div class="cta-inner cc-dark">
                                <div><strong><?php echo e(__('careers.hero.open_inq')); ?></strong></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section kc-careers-manifesto">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading cc-challenge"><?php echo e(__('careers.manifesto.h2_l1')); ?><br><?php echo e(__('careers.manifesto.h2_l2')); ?></h2>
                    <p class="subheading cc-features" style="color:#c8c8c8"><?php echo e(__('careers.manifesto.p1')); ?></p>
                    <p class="subheading cc-features" style="color:#c8c8c8"><?php echo e(__('careers.manifesto.p2')); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="open-roles">
        <div class="page-padding">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-heading cc-open-role"><?php echo e(__('careers.roles.heading')); ?></h2>
                    <p class="subheading cc-features"><?php echo e(__('careers.roles.intro')); ?></p>
                </div>

                <?php if(session('career_success')): ?>
                    <div class="kc-careers-flash kc-careers-flash--ok"><?php echo e(session('career_success')); ?></div>
                <?php endif; ?>
                <?php if($errors->any()): ?>
                    <div class="kc-careers-flash kc-careers-flash--err">
                        <ul class="mb-0 list-inside list-disc pl-0">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if($vacancies->isEmpty()): ?>
                    <p class="subheading cc-features" style="text-align:center;max-width:520px;margin:0 auto 32px"><?php echo e(__('careers.roles.empty')); ?></p>
                <?php else: ?>
                    <div class="kc-role-list">
                        <?php $__currentLoopData = $vacancies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <details class="kc-role-details" <?php if(old('_form') === 'vacancy-'.$job->id): ?> open <?php endif; ?>>
                                <summary class="kc-role-summary">
                                    <span class="kc-role-summary__start">
                                        <?php echo $__env->make('careers.partials.vacancy-icon', ['icon' => $job->resolvedIcon()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <span class="kc-role-summary__main">
                                        <span class="kc-role-title"><?php echo e($job->localizedTitle()); ?></span>
                                        <span class="kc-role-meta">
                                            <?php if($job->localizedLocation()): ?><span><?php echo e($job->localizedLocation()); ?></span><?php endif; ?>
                                            <?php if($job->localizedContractType()): ?><span class="kc-role-dot">·</span><span><?php echo e($job->localizedContractType()); ?></span><?php endif; ?>
                                        </span>
                                        </span>
                                    </span>
                                    <span class="kc-role-summary__chev" aria-hidden="true">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    </span>
                                </summary>
                                <div class="kc-role-body">
                                    <div class="kc-role-desc subheading cc-features"><?php echo nl2br(e($job->localizedDescription())); ?></div>
                                    <form class="kc-apply-form" method="post" action="<?php echo e(route('careers.apply', $job)); ?>" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="_form" value="vacancy-<?php echo e($job->id); ?>">
                                        <div class="kc-apply-grid">
                                            <div>
                                                <label class="kc-apply-label"><?php echo e(__('careers.form.full_name')); ?></label>
                                                <input type="text" name="full_name" class="kc-apply-input" required maxlength="120" value="<?php echo e(old('full_name')); ?>">
                                            </div>
                                            <div>
                                                <label class="kc-apply-label"><?php echo e(__('careers.form.email')); ?></label>
                                                <input type="email" name="email" class="kc-apply-input" required value="<?php echo e(old('email')); ?>">
                                            </div>
                                            <div>
                                                <label class="kc-apply-label"><?php echo e(__('careers.form.telegram')); ?></label>
                                                <input type="text" name="telegram_username" class="kc-apply-input" required maxlength="120" placeholder="<?php echo e(__('careers.form.telegram_ph')); ?>" value="<?php echo e(old('telegram_username')); ?>">
                                            </div>
                                            <div class="kc-apply-span2">
                                                <label class="kc-apply-label"><?php echo e(__('careers.form.cover')); ?></label>
                                                <textarea name="cover_message" class="kc-apply-input kc-apply-textarea" rows="4" maxlength="8000" placeholder="<?php echo e(__('careers.form.cover_ph')); ?>"><?php echo e(old('cover_message')); ?></textarea>
                                            </div>
                                            <div class="kc-apply-span2">
                                                <label class="kc-apply-label"><?php echo e(__('careers.form.cv')); ?></label>
                                                <input type="file" name="cv" class="kc-apply-file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                            </div>
                                        </div>
                                        <button type="submit" class="cta w-inline-block kc-apply-submit">
                                            <div class="cta-bg u-rainbow u-blur-perf"></div>
                                            <div class="cta-inner"><div><strong><?php echo e(__('careers.form.submit_apply')); ?></strong></div></div>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section kc-careers-inquiry-wrap" id="open-inquiry">
        <div class="page-padding">
            <div class="container">
                <div class="section-header cc-legal">
                    <h2 class="heading-m"><?php echo e(__('careers.inquiry.heading')); ?></h2>
                    <p class="subheading cc-features"><?php echo e(__('careers.inquiry.intro')); ?></p>
                </div>
                <div class="kc-inquiry-card">
                    <form method="post" action="<?php echo e(route('careers.inquiry')); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="_form" value="inquiry">
                        <div class="kc-apply-grid">
                            <div>
                                <label class="kc-apply-label"><?php echo e(__('careers.form.full_name')); ?></label>
                                <input type="text" name="full_name" class="kc-apply-input" required maxlength="120" value="<?php echo e(old('full_name')); ?>">
                            </div>
                            <div>
                                <label class="kc-apply-label"><?php echo e(__('careers.form.email')); ?></label>
                                <input type="email" name="email" class="kc-apply-input" required value="<?php echo e(old('email')); ?>">
                            </div>
                            <div>
                                <label class="kc-apply-label"><?php echo e(__('careers.form.telegram')); ?></label>
                                <input type="text" name="telegram_username" class="kc-apply-input" required maxlength="120" placeholder="@username" value="<?php echo e(old('telegram_username')); ?>">
                            </div>
                            <div class="kc-apply-span2">
                                <label class="kc-apply-label"><?php echo e(__('careers.form.message')); ?></label>
                                <textarea name="message" class="kc-apply-input kc-apply-textarea" rows="6" required minlength="20" maxlength="8000" placeholder="<?php echo e(__('careers.form.message_ph')); ?>"><?php echo e(old('message')); ?></textarea>
                            </div>
                            <div class="kc-apply-span2">
                                <label class="kc-apply-label"><?php echo e(__('careers.form.attachment')); ?></label>
                                <input type="file" name="attachment" class="kc-apply-file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            </div>
                        </div>
                        <button type="submit" class="cta w-inline-block kc-apply-submit">
                            <div class="cta-bg u-rainbow u-blur-perf cc-dark"></div>
                            <div class="cta-inner cc-dark"><div><strong><?php echo e(__('careers.form.submit_inq')); ?></strong></div></div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('legal.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/careers/index.blade.php ENDPATH**/ ?>