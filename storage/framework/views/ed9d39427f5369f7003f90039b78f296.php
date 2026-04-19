<?php $__env->startSection('title', 'Karyera #'.$careerApplication->id); ?>
<?php $__env->startSection('page-title', 'Karyera #'.$careerApplication->id); ?>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.career-applications.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.career-applications.index')).'']); ?>
     <?php $__env->slot('heading', null, []); ?> Ariza #<?php echo e($careerApplication->id); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('meta', null, []); ?> <?php echo e($careerApplication->created_at?->format('d.m.Y H:i')); ?> <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <?php if($careerApplication->cv_path): ?>
            <a href="<?php echo e(route('panel.career-applications.cv', $careerApplication)); ?>" class="btn-p ghost">
                <i class="bi bi-file-earmark-arrow-down"></i> CV yuklab olish
            </a>
        <?php endif; ?>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $attributes = $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a)): ?>
<?php $component = $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a; ?>
<?php unset($__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a); ?>
<?php endif; ?>

<?php if(session('success')): ?>
    <div class="mb-3 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-200"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        <div class="p-card">
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
            <div class="p-card">
                <div class="dash-card-head"><div class="dash-card-title"><?php echo e($careerApplication->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'Murojaat matni' : 'Qisqa xat / izoh'); ?></div></div>
                <div class="dash-card-body">
                    <div class="p-quote-block"><?php echo e($careerApplication->cover_message); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="p-card">
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

        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Nomzodga email yuborish</div></div>
            <div class="dash-card-body">
                <p class="mb-2 text-xs text-[var(--p-hint)]">Xabar <strong><?php echo e(config('mail.from.address')); ?></strong> manzilidan <strong><?php echo e($careerApplication->email); ?></strong> ga yuboriladi (Reply-To: <?php echo e(config('mail.reply_to.address')); ?>).</p>
                <form method="POST" action="<?php echo e(route('panel.career-applications.reply', $careerApplication)); ?>">
                    <?php echo csrf_field(); ?>
                    <textarea name="body" class="p-form-control mb-2" rows="6" required minlength="5" maxlength="12000" placeholder="Javob matni…"><?php echo e(old('body')); ?></textarea>
                    <button type="submit" class="btn-p primary"><i class="bi bi-send"></i> Yuborish</button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <div class="p-card">
            <div class="dash-card-head"><div class="dash-card-title">Holat</div></div>
            <div class="dash-card-body">
                <?php $st = $statuses[$careerApplication->status] ?? ['label' => $careerApplication->status, 'class' => 'ob-p']; ?>
                <span class="o-badge <?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></span>
                <form method="POST" action="<?php echo e(route('panel.career-applications.status', $careerApplication)); ?>" class="mt-3">
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
            <div class="p-card">
                <div class="dash-card-head"><div class="dash-card-title">O‘qilgan</div></div>
                <div class="dash-card-body text-xs text-[var(--p-hint)]"><?php echo e($careerApplication->read_at->format('d.m.Y H:i')); ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/career-applications/show.blade.php ENDPATH**/ ?>