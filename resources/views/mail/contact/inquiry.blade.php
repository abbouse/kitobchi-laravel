<x-mail::message>
# Saytdan yangi murojaat

@php
    $topicLabels = [
        'kitobchi' => 'Kitobchi (xaridorlar ilovasi)',
        'business' => 'Kitobchi Business (sotuvchilar)',
        'express' => 'Kitobchi Express (kuryerlar)',
        'other' => 'Boshqa mavzu',
    ];
@endphp

**Kimdan:** {{ $fullName }}<br>
**Telefon:** {{ $phone }}<br>
**Email:** {{ $email !== '' ? $email : '— ko‘rsatilmagan' }}<br>
**Mavzu:** {{ $topicLabels[$topic] ?? $topic }}<br>
**Sayt tili:** {{ strtoupper($locale) }}

<x-mail::panel>
{{ $messageBody }}
</x-mail::panel>

@if($email !== '')
Javob berish uchun shu xatga to‘g‘ridan-to‘g‘ri reply qiling — javob {{ $email }} manziliga boradi.
@endif

<x-mail::subcopy>
Bu xabar {{ config('app.url') }}/contact sahifasidagi forma orqali avtomatik yuborildi.
</x-mail::subcopy>
</x-mail::message>
