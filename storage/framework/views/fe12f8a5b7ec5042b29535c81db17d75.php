<?php $__env->startSection('title', 'Kirish'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $nextTheme = session('theme', 'dark') === 'dark' ? 'light' : 'dark';
?>

<div class="relative flex min-h-screen flex-col justify-center lg:flex-row dark:bg-gray-900">
    
    <div class="relative flex w-full flex-1 flex-col justify-center px-4 py-10 sm:px-6 lg:w-1/2 lg:px-8 xl:px-12">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_90%_55%_at_50%_-10%,rgba(70,95,255,0.09),transparent_55%)] dark:bg-[radial-gradient(ellipse_90%_50%_at_50%_-8%,rgba(117,146,255,0.12),transparent_58%)]"></div>
        <div class="relative mx-auto w-full max-w-md">
            <div class="mb-8 flex items-center justify-between gap-4">
                <a href="<?php echo e(url('/')); ?>"
                   class="inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90">
                    <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Saytga qaytish
                </a>
                <form method="POST" action="<?php echo e(route('panel.theme')); ?>"
                      class="inline-flex"
                      onsubmit="localStorage.setItem('kitobchi_theme', this.querySelector('[name=theme]').value)">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="theme" value="<?php echo e($nextTheme); ?>"/>
                    <button type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                            title="<?php echo e($nextTheme === 'dark' ? 'Qorong‘i' : 'Yorug‘'); ?> rejim">
                        <?php if(session('theme', 'dark') === 'dark'): ?>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                        <?php else: ?>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>
                        <?php endif; ?>
                    </button>
                </form>
            </div>

            <div class="mb-8">
                <a href="<?php echo e(route('panel.login')); ?>" class="mb-6 inline-flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-lg font-bold text-white shadow-lg shadow-brand-500/30">K</span>
                    <span>
                        <span class="block text-lg font-semibold text-gray-900 dark:text-white">kitobchi.</span>
                        <span class="text-theme-xs text-gray-500 dark:text-gray-400">Admin boshqaruvi</span>
                    </span>
                </a>
                <h1 class="text-title-sm sm:text-title-md mb-2 font-semibold text-gray-800 dark:text-white/90">
                    Xush kelibsiz
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tizimga kirish uchun email va parolingizni kiriting.
                </p>
            </div>

            <?php if(session('error')): ?>
                <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 shrink-0"></i>
                    <span><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('panel.login.post')); ?>"
                  class="space-y-5 rounded-2xl border border-gray-200/90 bg-white/90 p-6 shadow-[0_8px_30px_-12px_rgba(16,24,40,0.12)] backdrop-blur-sm sm:p-8 dark:border-gray-800/80 dark:bg-gray-900/75 dark:shadow-[0_12px_40px_-16px_rgba(0,0,0,0.45)]">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Email <span class="text-error-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email"
                           placeholder="admin@example.com"
                           class="<?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-error-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"/>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1.5 text-xs text-error-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Parol <span class="text-error-500">*</span>
                    </label>
                    <div x-data="{ showPassword: false }" class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                               placeholder="Parolingizni kiriting"
                               class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"/>
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute top-1/2 right-3 z-10 -translate-y-1/2 cursor-pointer rounded p-1 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/80"
                                aria-label="Parolni ko‘rsatish">
                            <svg x-show="!showPassword" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="currentColor"/></svg>
                            <svg x-show="showPassword" class="h-5 w-5 fill-current" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="currentColor"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-700 select-none dark:text-gray-400">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-600 dark:bg-gray-900"/>
                        <span>Meni eslab qol</span>
                    </label>
                </div>

                <button type="submit"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/20">
                    <i class="bi bi-box-arrow-in-right text-base"></i>
                    Tizimga kirish
                </button>
            </form>

            <p class="mt-8 text-center text-theme-xs text-gray-500 dark:text-gray-500">
                kitobchi.uz · Admin panel
            </p>
        </div>
    </div>

    
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand-500 p-10 text-white lg:flex xl:p-14">
        <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 h-80 w-80 rounded-full bg-black/10 blur-3xl"></div>
        <div class="relative z-10">
            <p class="text-sm font-medium uppercase tracking-wider text-white/80">Boshqaruv paneli</p>
            <h2 class="mt-4 max-w-md text-3xl font-semibold leading-tight tracking-tight xl:text-4xl">
                Kitob va buyurtmalarni bir joydan boshqaring
            </h2>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/85">
                Moderatsiya, buyurtmalar, sotuvchilar va tizim sozlamalari — xavfsiz va zamonaviy interfeys.
            </p>
        </div>
        <div class="relative z-10 text-sm text-white/70">
            © <?php echo e(date('Y')); ?> kitobchi.
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('panel.layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/auth/login.blade.php ENDPATH**/ ?>