<?php $__env->startSection('title', 'Yangi kitob'); ?>
<?php $__env->startSection('page-title', 'Yangi kitob qo\'shish'); ?>
<?php $__env->startSection('breadcrumb', 'Panel / Kitoblar / Yangi'); ?>

<?php $__env->startSection('content'); ?>

<?php if (isset($component)) { $__componentOriginal8e8786879b90e52c629eb07e5f5bbd4a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e8786879b90e52c629eb07e5f5bbd4a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel.page-header','data' => ['backHref' => ''.e(route('panel.books.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('panel.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['back-href' => ''.e(route('panel.books.index')).'']); ?>
   <?php $__env->slot('heading', null, []); ?> Yangi kitob qo'shish <?php $__env->endSlot(); ?>
   <?php $__env->slot('meta', null, []); ?> Kitobxona katalogiga yangi kitobi qo'shing <?php $__env->endSlot(); ?>
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


<form method="POST" action="<?php echo e(route('panel.books.store')); ?>" enctype="multipart/form-data">
  <?php echo csrf_field(); ?>

  <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-12 xl:gap-5">

    
    <div class="min-w-0 fade-up xl:col-span-7 2xl:col-span-8">
      <div class="p-card">
        <div class="p-card-title mb-4">Asosiy ma'lumotlar</div>
        
        <div class="mb-3">
          <label class="p-form-label">Kitob nomi <span style="color:var(--p-danger)">*</span></label>
          <input type="text" name="name" class="p-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                 value="<?php echo e(old('name')); ?>" required maxlength="255" placeholder="Kitobning to'liq nomini kiriting">
          <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div class="">
            <label class="p-form-label">Muallif <span style="color:var(--p-danger)">*</span></label>
            <input type="text" name="author" class="p-form-control <?php $__errorArgs = ['author'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('author')); ?>" required maxlength="100" placeholder="Muallif ismi">
            <?php $__errorArgs = ['author'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="">
            <label class="p-form-label">Kategoriya <span style="color:var(--p-danger)">*</span></label>
            <select name="category_id" class="p-form-control <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
              <option value="">— Kategoriyani tanlang —</option>
              <?php $__currentLoopData = $categories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cat->id); ?>" <?php echo e(old('category_id')==$cat->id?'selected':''); ?>>
                  <?php echo e($cat->name_uz); ?>

                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="p-form-label">Tafsili</label>
          <textarea name="description" class="p-form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    maxlength="1000" rows="4" placeholder="Kitob haqida qisqacha ma'lumot..."><?php echo e(old('description')); ?></textarea>
          <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="">
            <label class="p-form-label">Til</label>
            <select name="lang" class="p-form-control <?php $__errorArgs = ['lang'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="uz" <?php echo e(old('lang','uz')==='uz'?'selected':''); ?>>O'zbek</option>
              <option value="ru" <?php echo e(old('lang')==='ru'?'selected':''); ?>>Rus</option>
              <option value="en" <?php echo e(old('lang')==='en'?'selected':''); ?>>Ingliz</option>
              <option value="other" <?php echo e(old('lang')==='other'?'selected':''); ?>>Boshqa</option>
            </select>
            <?php $__errorArgs = ['lang'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="">
            <label class="p-form-label">Muqova turi</label>
            <select name="coverType" class="p-form-control <?php $__errorArgs = ['coverType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="hardcover" <?php echo e(old('coverType','hardcover')==='hardcover'?'selected':''); ?>>Qattiq muqova</option>
              <option value="softcover" <?php echo e(old('coverType')==='softcover'?'selected':''); ?>>Yumshoq muqova</option>
              <option value="paperback" <?php echo e(old('coverType')==='paperback'?'selected':''); ?>>Qog'oz muqova</option>
            </select>
            <?php $__errorArgs = ['coverType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">Sahifalar soni</label>
            <input type="number" name="pages" class="p-form-control <?php $__errorArgs = ['pages'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('pages')); ?>" min="0" placeholder="0">
            <?php $__errorArgs = ['pages'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">ISBN</label>
            <input type="text" name="isbn" class="p-form-control <?php $__errorArgs = ['isbn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('isbn')); ?>" maxlength="20" placeholder="978-0-xxx-xxxxx-x">
            <?php $__errorArgs = ['isbn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="md:col-span-4">
            <label class="p-form-label">Chop etilgan yil</label>
            <input type="number" name="publishYear" class="p-form-control <?php $__errorArgs = ['publishYear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   value="<?php echo e(old('publishYear')); ?>" min="1900" max="<?php echo e(date('Y')+1); ?>" placeholder="<?php echo e(date('Y')); ?>">
            <?php $__errorArgs = ['publishYear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>
      </div>
    </div>

    
    <div class="min-w-0 fade-up xl:col-span-5 2xl:col-span-4">
      
      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Narxlar (UZS)</div>
        
        <div class="mb-3">
          <label class="p-form-label">Asosiy narx <span style="color:var(--p-danger)">*</span></label>
          <input type="number" name="price" class="p-form-control <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                 value="<?php echo e(old('price')); ?>" min="0" step="100" required placeholder="0">
          <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-3">
          <label class="p-form-label">Chegirma narxi</label>
          <input type="number" name="discountPrice" class="p-form-control <?php $__errorArgs = ['discountPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                 value="<?php echo e(old('discountPrice')); ?>" min="0" step="100" placeholder="0">
          <?php $__errorArgs = ['discountPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="mb-3">
          <label class="p-form-label">Zaxira (dona) <span style="color:var(--p-danger)">*</span></label>
          <input type="number" name="count" class="p-form-control <?php $__errorArgs = ['count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                 value="<?php echo e(old('count', 0)); ?>" min="0" required placeholder="0">
          <?php $__errorArgs = ['count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
      </div>

      
      <div class="p-card mb-3">
        <div class="p-card-title mb-3">Muqova rasmi</div>
        
        <div style="border:2px dashed var(--p-border);border-radius:10px;padding:20px;text-align:center;cursor:pointer;transition:all .2s"
             id="imageDropZone" class="image-drop-zone">
          <input type="file" name="images" id="imageInput" accept="image/*" style="display:none" class="<?php $__errorArgs = ['images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          
          <div style="font-size:32px;color:var(--p-muted);margin-bottom:8px">
            <i class="bi bi-cloud-arrow-up"></i>
          </div>
          <div style="font-size:14px;font-weight:600;color:var(--p-text);margin-bottom:4px">
            Rasmni yuklang
          </div>
          <div style="font-size:12px;color:var(--p-hint)">
            yoki qo'shish uchun ustiga bosing
          </div>
        </div>

        <div id="imagePreview" style="margin-top:12px;display:none">
          <img id="previewImg" style="max-width:100%;border-radius:8px;max-height:300px">
        </div>

        <?php $__errorArgs = ['images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback" style="color:var(--p-danger)"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div class="p-card">
        <div class="p-card-title mb-3">Holat</div>
        
        <div class="mb-3">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px">
            <input type="hidden" name="status" value="0">
            <input type="checkbox" name="status" value="1"
                   <?php echo e(old('status') ? 'checked' : ''); ?>

                   style="width:18px;height:18px;accent-color:var(--p-accent)">
            <span style="font-size:13px;color:var(--p-text)">Marketplace da ko'rsatish</span>
          </label>
        </div>

        <div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="hidden" name="is_hidden" value="0">
            <input type="checkbox" name="is_hidden" value="1"
                   <?php echo e(old('is_hidden') ? 'checked' : ''); ?>

                   style="width:18px;height:18px;accent-color:var(--p-danger)">
            <span style="font-size:13px;color:var(--p-text)">Yashirinli</span>
          </label>
        </div>
      </div>
    </div>
  </div>

  
  <div class="flex gap-2 justify-end fade-up d3">
    <a href="<?php echo e(route('panel.books.index')); ?>" class="btn-p ghost">Bekor qilish</a>
    <button type="submit" class="btn-p primary">
      <i class="bi bi-plus-circle"></i> Yangi kitob yaratish
    </button>
  </div>
</form>

<?php $__env->startPush('styles'); ?>
<style>
.image-drop-zone {
  transition: all 0.3s ease;
}
.image-drop-zone:hover {
  background: var(--p-elevated);
  border-color: var(--p-accent);
}
.image-drop-zone.dragover {
  background: var(--p-accent-d);
  border-color: var(--p-accent);
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const dropZone = document.getElementById('imageDropZone');
const imageInput = document.getElementById('imageInput');
const previewImg = document.getElementById('previewImg');
const previewDiv = document.getElementById('imagePreview');

// Rasm dragging
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  const files = e.dataTransfer.files;
  if (files.length) {
    imageInput.files = files;
    handleImageSelect();
  }
});

dropZone.addEventListener('click', () => imageInput.click());

imageInput.addEventListener('change', handleImageSelect);

function handleImageSelect() {
  const file = imageInput.files[0];
  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      previewDiv.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/books/create.blade.php ENDPATH**/ ?>