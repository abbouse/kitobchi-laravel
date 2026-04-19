<?php
    $data = 'm=67988cbfdbc8d1a8dc0d74a7;ac.order_id=4;a=500';
    $b = base64_encode($data);
?>
<a href="https://checkout.paycom.uz/<?php echo e($b); ?>">Test uchun</a>



<form method="POST" action="https://checkout.paycom.uz">

    <!-- Идентификатор WEB Кассы -->
    <input type="hidden" name="merchant" value="67988cbfdbc8d1a8dc0d74a7"/>

    <!-- Сумма платежа в тийинах -->
    <input type="hidden" name="amount" value="500"/>

    <!-- Поля Объекта Account -->
    <input type="hidden" name="account[order_id]" value="4"/>
    
    <button type="submit">Оплатить с помощью <b>Payme</b></button>
</form><?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/payme.blade.php ENDPATH**/ ?>