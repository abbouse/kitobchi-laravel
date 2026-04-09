<?php $__env->startSection('title', 'Tahrirlash: '.$book->name); ?>
<?php $__env->startSection('page-title', 'Kitobni tahrirlash'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Kitoblar / Tahrirlash'); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header fade-up d-flex align-items-center gap-3">
  <a href="<?php echo e(route('panel.books.show', $book)); ?>" class="btn-p ghost icon"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h1 class="page-title"><?php echo e($book->name); ?></h1>
    <p class="page-sub">ID: #<?php echo e($book->id); ?> · <?php echo e($book->author); ?></p>
  </div>
</div>


<div style="padding:12px 16px;border-radius:10px;background:var(--p-info-d,rgba(56,189,248,0.10));border:1px solid rgba(56,189,248,0.2);font-size:13px;color:var(--p-info,#38bdf8);margin-bottom:20px;display:flex;align-items:center;gap:10px">
  <i class="bi bi-info-circle-fill"></i>
  Kitob ma'lumotlari sotuvchi tomonidan kiritilgan. Faqat moderatsiya, holat va narq tahrirlash mumkin.
</div>

<form method="POST" action="<?php echo e(route('panel.books.update', $book)); ?>">
  <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

  <div class="row g-3">

    
    <div class="col-xl-7 fade-up d1">
      <div class="p-card h-100">
        <div class="p-card-title mb-3">Kitob ma'lumotlari (faqat ko'rish)</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="p-form-label">Kitob nomi</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->name); ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Muallif</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->author); ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Kategoriya</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->category?->name_uz ?? '—'); ?>" disabled>
          </div>
          <div class="col-md-6">
            <label class="p-form-label">Sotuvchi</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->seller?->shop_name ?? '—'); ?>" disabled>
          </div>
          <div class="col-md-4">
            <label class="p-form-label">Til</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->lang); ?>" disabled>
          </div>
          <div class="col-md-4">
            <label class="p-form-label">Muqova</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->coverType); ?>" disabled>
          </div>
          <div class="col-md-4">
            <label class="p-form-label">Sahifalar</label>
            <input type="text" class="p-form-control" value="<?php echo e($book->pages); ?> bet" disabled>
          </div>
        </div>
      </div>
    </div>

    
    <div class="col-xl-5 fade-up d2">

      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Narxlar</div>
        <div class="mb-3">
          <label class="p-form-label">Asosiy narx (UZS)</label>
          <input type="number" name="price" class="p-form-control"
                 value="<?php echo e(old('price', $book->price)); ?>" min="0" step="100">
        </div>
        <div class="mb-3">
          <label class="p-form-label">Chegirma narxi (UZS)</label>
          <input type="number" name="discountPrice" class="p-form-control"
                 value="<?php echo e(old('discountPrice', $book->discountPrice)); ?>" min="0" step="100">
        </div>
        <div>
          <label class="p-form-label">Zaxira (dona)</label>
          <input type="number" name="count" class="p-form-control"
                 value="<?php echo e(old('count', $book->count)); ?>" min="0">
        </div>
      </div>

      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Moderatsiya</div>
        <div class="mb-3">
          <label class="p-form-label">Moderatsiya holati</label>
          <select name="is_approved" class="p-form-control">
            <option value="0" <?php echo e(old('is_approved',$book->is_approved)==='0'?'selected':''); ?>>⟳ Kutilmoqda</option>
            <option value="1" <?php echo e(old('is_approved',$book->is_approved)==='1'?'selected':''); ?>>✓ Tasdiqlangan</option>
            <option value="2" <?php echo e(old('is_approved',$book->is_approved)==='2'?'selected':''); ?>>✗ Rad etilgan</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="p-form-label">Marketplace ko'rinishi</label>
          <select name="status" class="p-form-control">
            <option value="1" <?php echo e(old('status',$book->status)?'selected':''); ?>>Ko'rinadi</option>
            <option value="0" <?php echo e(!old('status',$book->status)?'selected':''); ?>>Ko'rinmaydi</option>
          </select>
        </div>
        <div class="d-flex align-items-center justify-content-between"
             style="padding:10px;background:var(--p-elevated);border-radius:8px">
          <div>
            <div style="font-size:13px;font-weight:500;color:var(--p-text)">Yashirin</div>
            <div style="font-size:11px;color:var(--p-hint)">Hech qayerda ko'rinmaydi</div>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="is_hidden" value="1"
                   <?php echo e(old('is_hidden',$book->is_hidden) ? 'checked':''); ?>>
          </div>
        </div>
      </div>
    </div>

    
    <div class="col-12 fade-up d3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn-p primary"><i class="bi bi-check-lg"></i> Saqlash</button>
        <a href="<?php echo e(route('panel.books.show',$book)); ?>" class="btn-p ghost">Bekor qilish</a>
      </div>
    </div>

  </div>
</form>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/www-root/data/www/kitobchi.com/resources/views/panel/books/edit.blade.php ENDPATH**/ ?>