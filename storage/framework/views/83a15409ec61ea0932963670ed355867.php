<div class="mx-2 my-1">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex space-x-1">
            <span class="text-yellow"><?php echo e($key); ?></span>
            <span class="flex-1 content-repeat-['.']"></span>
            <span class="text-blue"><?php echo e($value); ?></span>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi-laravel/vendor/nutgram/laravel/resources/views/terminal/table.blade.php ENDPATH**/ ?>