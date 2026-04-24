<?php $__env->startSection('title', 'Yangi foydalanuvchi'); ?>
<?php $__env->startSection('page-title', 'Yangi foydalanuvchi'); ?>

<?php $__env->startSection('content'); ?>
<h2 class="text-2xl font-bold tracking-tight mb-6">Yangi foydalanuvchi qo'shish</h2>
<?php echo $__env->make('a122.users._form', ['user' => null, 'action' => route('admin.users.store'), 'method' => 'POST'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('a122.layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/resources/views/a122/users/create.blade.php ENDPATH**/ ?>