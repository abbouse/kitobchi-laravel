<x-mail::message>
# Salom, {{ $application->full_name }}

Kitobchi jamoasi sizning **{{ $application->type === \App\Models\CareerApplication::TYPE_INQUIRY ? 'ochiq murojaatingizni' : 'vakansiyaga arizangizni' }}** qabul qildi.

Tez orada ko‘rib chiqamiz. Savollar bo‘lsa, shu xatga javob berishingiz mumkin.

Rahmat,<br>
{{ config('mail.from.name', 'Kitobchi') }}

<x-mail::subcopy>
Bu xabar {{ config('app.url') }} orqali yuborilgan ariza asosida avtomatik yuborildi.
</x-mail::subcopy>
</x-mail::message>
