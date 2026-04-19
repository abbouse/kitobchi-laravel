<x-mail::message>
# Salom, {{ $application->full_name }}

{!! nl2br(e($bodyText)) !!}

---

<x-mail::subcopy>
Xabar: {{ config('mail.from.address', 'noreply@kitobchi.com') }} (Kitobchi)
@if($application->telegram_username)
<br>Telegram: t.me/{{ $application->telegram_username }}
@endif
</x-mail::subcopy>
</x-mail::message>
