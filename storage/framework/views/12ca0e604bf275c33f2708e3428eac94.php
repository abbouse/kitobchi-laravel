<?php $__env->startSection('title', 'Kirish'); ?>

<?php $__env->startSection('content'); ?>
<section class="a122-login-shell">
  <div class="a122-login-grid">
    <div class="a122-login-brand">
      <a href="<?php echo e(url('/')); ?>" class="a122-login-brand__back">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Saytga qaytish
      </a>

      <div class="a122-login-brand__content">
        <div class="a122-login-badge">Kitobdan katta sherik yo'q</div>
        <h1 class="a122-login-brand__title">Har bir yaxshi kun</h1>
        <p class="a122-login-brand__desc">yaxshi fikr bilan boshlanadi, yaxshi fikr esa ko‘pincha bir sahifadan tug‘iladi.</p>

        <div class="a122-login-brand__stats">
          <div class="a122-login-stat">
            <div class="a122-login-stat__value">Sokinlik</div>
            <div class="a122-login-stat__label">shoshmasdan, aniq qaror qilish uchun</div>
          </div>
          <div class="a122-login-stat">
            <div class="a122-login-stat__value">Fikr</div>
            <div class="a122-login-stat__label">har bir sahifa yangi nuqtai nazar beradi</div>
          </div>
          <div class="a122-login-stat">
            <div class="a122-login-stat__value">Ilhom</div>
            <div class="a122-login-stat__label">bugungi ishni ma’noli davom ettirish uchun</div>
          </div>
        </div>
      </div>
    </div>

    <div class="a122-login-card-wrap">
      <div class="a122-login-copy">
        <div class="flex items-center justify-between gap-3">
          <h2 class="a122-login-title">Tizimga kirish</h2>
          <button data-theme-toggle class="topbar-theme-btn" aria-label="Tema almashtirish" title="Light / Dark mode">
            <span class="theme-icon-light"><i data-lucide="sun-medium" class="w-4.5 h-4.5"></i></span>
            <span class="theme-icon-dark"><i data-lucide="moon-star" class="w-4.5 h-4.5"></i></span>
          </button>
        </div>
        <p class="a122-login-sub">Panelga kirish uchun email va parolni kiriting.</p>
      </div>

      <?php if(session('error')): ?>
        <div class="p-alert danger">
          <i class="bi bi-exclamation-circle"></i>
          <span><?php echo e(session('error')); ?></span>
        </div>
      <?php endif; ?>

      <?php if(session('success')): ?>
        <div class="p-alert success">
          <i class="bi bi-check-circle"></i>
          <span><?php echo e(session('success')); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo e(route('admin.login.post')); ?>" class="a122-login-form">
        <?php echo csrf_field(); ?>
        <div>
          <label for="email" class="p-form-label">Email</label>
          <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email" placeholder="admin@example.com" class="p-form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="invalid-feedback"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div x-data="{ showPassword: false }">
          <label for="password" class="p-form-label">Parol</label>
          <div class="relative">
            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" placeholder="Parolingizni kiriting" class="p-form-control pr-11">
            <button type="button" @click="showPassword = !showPassword" class="a122-password-toggle" aria-label="Parolni ko‘rsatish">
              <i x-show="!showPassword" data-lucide="eye" class="w-4 h-4"></i>
              <i x-show="showPassword" data-lucide="eye-off" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <label class="a122-login-check">
          <input type="checkbox" name="remember" value="1" class="rounded">
          <span>Meni eslab qol</span>
        </label>

        <button type="submit" class="btn-p primary w-full">
          <i class="bi bi-box-arrow-in-right"></i>
          Tizimga kirish
        </button>
      </form>

      <div class="a122-login-footer">© <?php echo e(date('Y')); ?> Kitobchi ecosystems</div>
    </div>
  </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/a122/auth/login.blade.php ENDPATH**/ ?>