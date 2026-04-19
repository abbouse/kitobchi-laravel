
<div class="flex items-center gap-2">
  <div style="width:28px;height:28px;border-radius:50%;overflow:hidden;flex-shrink:0;
              background:linear-gradient(135deg,var(--p-accent),#7c5cfc);
              display:flex;align-items:center;justify-content:center;
              font-size:11px;font-weight:700;color:#fff">
    <?php if($avatar ?? null): ?>
      <img src="<?php echo e($avatar); ?>" style="width:100%;height:100%;object-fit:cover">
    <?php else: ?>
      <?php echo e(strtoupper(substr($name??'U',0,1))); ?>

    <?php endif; ?>
  </div>
  <div>
    <?php if($id ?? null): ?>
    <a href="<?php echo e(route('panel.users.show',$id)); ?>"
       style="font-size:12.5px;font-weight:500;color:var(--p-text);text-decoration:none">
      <?php echo e($name); ?> <?php echo e($lname); ?>

    </a>
    <?php else: ?>
    <div style="font-size:12.5px;color:var(--p-text)"><?php echo e($name); ?> <?php echo e($lname); ?></div>
    <?php endif; ?>
  </div>
</div><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/chats/_user-cell.blade.php ENDPATH**/ ?>