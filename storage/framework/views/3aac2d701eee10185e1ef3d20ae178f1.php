<?php $__env->startSection('title', 'Karyera #'.$careerApplication->id); ?>
<?php $__env->startSection('page-title', 'Karyera #'.$careerApplication->id); ?>

<?php $__env->startSection('content'); ?>
<?php
    $messageCount = $careerApplication->messages->count();
    $applicationTypeLabel = $careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Ochiq murojaat' : 'Vakansiya arizasi';
?>
<?php if (isset($component)) { $__componentOriginal0c1345684b2d774f43a544669f5684b0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c1345684b2d774f43a544669f5684b0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.a122.page-header','data' => ['backHref' => ''.e(route('admin.job-applications.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('a122.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('admin.job-applications.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> Ariza #<?php echo e($careerApplication->id); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($careerApplication->created_at?->format('d.m.Y H:i')); ?> · <?php echo e($applicationTypeLabel); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <?php if($careerApplication->cv_path): ?>
            <a href="<?php echo e(route('admin.job-applications.cv', $careerApplication)); ?>" class="btn-p ghost">
                <i class="bi bi-file-earmark-arrow-down"></i> CV yuklab olish
            </a>
        <?php endif; ?>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $attributes = $__attributesOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__attributesOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c1345684b2d774f43a544669f5684b0)): ?>
<?php $component = $__componentOriginal0c1345684b2d774f43a544669f5684b0; ?>
<?php unset($__componentOriginal0c1345684b2d774f43a544669f5684b0); ?>
<?php endif; ?>

<section class="a122-section mb-4">
    <div class="a122-section-body">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="kpi-soft">
                <div class="metric-label">Ariza turi</div>
                <div class="metric-value text-xl"><?php echo e($applicationTypeLabel); ?></div>
                <div class="metric-meta">Kanal tipi</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Status</div>
                <div class="metric-value text-xl"><?php echo e($statuses[$careerApplication->status]['label'] ?? $careerApplication->status); ?></div>
                <div class="metric-meta">Joriy bosqich</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">Xabarlar</div>
                <div class="metric-value text-xl"><?php echo e(number_format($messageCount)); ?></div>
                <div class="metric-meta">Ichki tarix</div>
            </div>
            <div class="kpi-soft">
                <div class="metric-label">CV</div>
                <div class="metric-value text-xl"><?php echo e($careerApplication->cv_path ? 'Bor' : 'Yo‘q'); ?></div>
                <div class="metric-meta">Fayl mavjudligi</div>
            </div>
        </div>
    </div>
</section>

<?php if(session('success')): ?>
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="lg:col-span-2 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <?php $__currentLoopData = [
                ['Nomzod', $careerApplication->full_name, 'user-round'],
                ['Email', $careerApplication->email, 'mail'],
                ['Telegram', $careerApplication->telegram_username ? '@'.$careerApplication->telegram_username : '—', 'send'],
                ['Holat', ($statuses[$careerApplication->status]['label'] ?? $careerApplication->status), 'badge-check'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card-panel">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--p-soft)] text-[var(--p-accent)]">
                            <i data-lucide="<?php echo e($icon); ?>" class="h-4.5 w-4.5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-[0.16em] text-[var(--p-hint)]"><?php echo e($label); ?></div>
                            <div class="truncate text-sm font-medium text-[var(--p-text)]"><?php echo e($value); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="card-panel">
            <div class="dash-card-head"><div class="dash-card-title">Nomzod</div></div>
            <div class="dash-card-body space-y-2 p-body-relaxed text-sm">
                <div><span class="text-[var(--p-hint)]">Ism:</span> <strong><?php echo e($careerApplication->full_name); ?></strong></div>
                <div><span class="text-[var(--p-hint)]">Email:</span> <?php echo e($careerApplication->email); ?></div>
                <div><span class="text-[var(--p-hint)]">Telegram:</span>
                    <?php if($careerApplication->telegram_username): ?>
                        <a href="https://t.me/<?php echo e($careerApplication->telegram_username); ?>" target="_blank" rel="noopener" class="text-[var(--p-accent)]">{{ $careerApplication->telegram_username }}</a>
                    <?php else: ?> — <?php endif; ?>
                </div>
                <div><span class="text-[var(--p-hint)]">Turi:</span>
                    <?php echo e($careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Ochiq murojaat' : 'Vakansiya'); ?>

                </div>
                <?php if($careerApplication->vacancy): ?>
                    <div><span class="text-[var(--p-hint)]">Lavozim:</span> <?php echo e($careerApplication->vacancy->title); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($careerApplication->cover_message): ?>
            <div class="card-panel">
                <div class="dash-card-head"><div class="dash-card-title"><?php echo e($careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat matni' : 'Qisqa xat / izoh'); ?></div></div>
                <div class="dash-card-body">
                    <div class="p-quote-block"><?php echo e($careerApplication->cover_message); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card-panel">
            <div class="dash-card-head"><div class="dash-card-title">Xabarlar</div></div>
            <div class="dash-card-body space-y-3">
                <?php $__currentLoopData = $careerApplication->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-msg-block">
                        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-[var(--p-hint)]">
                            <span class="s-pill <?php echo e($msg->sender === \App\Models\CareerApplicationMessage::SENDER_ADMIN ? 'accent' : 'muted'); ?>">
                                <?php echo e($msg->sender === \App\Models\CareerApplicationMessage::SENDER_ADMIN ? 'Admin' : 'Tizim'); ?>

                            </span>
                            <?php if($msg->admin): ?>
                                <span><?php echo e($msg->admin->name ?? $msg->admin->email ?? 'Admin #'.$msg->admin_id); ?></span>
                            <?php endif; ?>
                            <span><?php echo e($msg->created_at?->format('d.m.Y H:i')); ?></span>
                        </div>
                        <div class="p-msg-text"><?php echo e($msg->body); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="card-panel">
            <div class="dash-card-head"><div class="dash-card-title">Nomzodga email yuborish</div></div>
            <div class="dash-card-body">
                <p class="mb-2 text-xs text-[var(--p-hint)]">Xabar <strong><?php echo e(config('mail.from.address')); ?></strong> manzilidan <strong><?php echo e($careerApplication->email); ?></strong> ga yuboriladi (Reply-To: <?php echo e(config('mail.reply_to.address')); ?>).</p>
                <form method="POST" action="<?php echo e(route('admin.job-applications.reply', $careerApplication)); ?>">
                    <?php echo csrf_field(); ?>
                    <textarea name="body" class="p-form-control mb-2" rows="6" required minlength="5" maxlength="12000" placeholder="Javob matni…"><?php echo e(old('body')); ?></textarea>
                    <button type="submit" class="btn-p primary"><i class="bi bi-send"></i> Yuborish</button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <div class="card-panel">
            <div class="dash-card-head"><div class="dash-card-title">Holat</div></div>
            <div class="dash-card-body">
                <?php $st = $statuses[$careerApplication->status] ?? ['label' => $careerApplication->status, 'class' => 'ob-p']; ?>
                <span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span>
                <form method="POST" action="<?php echo e(route('admin.job-applications.status', $careerApplication)); ?>" class="mt-3">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <label class="p-label text-xs text-[var(--p-hint)]">O‘zgartirish</label>
                    <select name="status" class="p-form-control mt-1" onchange="this.form.submit()">
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e($careerApplication->status === $key ? 'selected' : ''); ?>><?php echo e($s['label']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if($careerApplication->read_at): ?>
            <div class="card-panel">
                <div class="dash-card-head"><div class="dash-card-title">O‘qilgan</div></div>
                <div class="dash-card-body text-xs text-[var(--p-hint)]"><?php echo e($careerApplication->read_at->format('d.m.Y H:i')); ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/job-applications/show.blade.php ENDPATH**/ ?>