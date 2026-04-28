@extends('a122.layouts.admin')
@section('title', 'Client API Docs')
@section('page-title', 'Client API Docs')

@section('content')
<div class="space-y-4">
  <x-a122.page-header back-href="{{ route('admin.api-clients.index') }}">
    <x-slot name="heading">Client API hujjatlari</x-slot>
    <x-slot name="meta">Tashqi marketlar va integratsiya sheriklari uchun o‘qish API. Hamma so‘rovlar <code>X-App-ID</code> va <code>X-App-Secret</code> bilan yuboriladi.</x-slot>
  </x-a122.page-header>

  <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-4">
    <section class="p-card fade-up">
      <div class="dash-card-head">
        <div class="dash-card-title">Autentifikatsiya</div>
      </div>

      <div class="space-y-3 text-sm text-[var(--p-hint)]">
        <p>Har bir request quyidagi sarlavhalar bilan yuboriladi:</p>
        <pre class="rounded-2xl bg-[var(--p-elevated)] p-4 text-xs overflow-x-auto"><code>X-App-ID: app_xxxxxxxxxxxx
X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json</code></pre>
        <p>Baza URL: <code>{{ url('/api/v1/client') }}</code></p>
        <p>Har bir client uchun alohida rate limit ishlaydi. Default limitlar: <code>{{ $defaultLimits['per_second'] }}/soniya</code> va <code>{{ $defaultLimits['per_minute'] }}/daqiqa</code>.</p>
      </div>

      <div class="mt-6 dash-card-head">
        <div class="dash-card-title">Endpointlar</div>
      </div>

      <div class="space-y-3">
        @foreach($endpoints as [$method, $path, $desc, $ability])
          <div class="rounded-2xl border border-[var(--p-border)] bg-[var(--p-surface)] p-4">
            <div class="flex flex-wrap items-center gap-2">
              <span class="s-pill accent">{{ $method }}</span>
              <code class="text-sm">{{ $path }}</code>
              <span class="s-pill">{{ $ability }}</span>
            </div>
            <div class="mt-2 text-sm text-[var(--p-text)]">{{ $desc }}</div>
          </div>
        @endforeach
      </div>

      <div class="mt-6 dash-card-head">
        <div class="dash-card-title">Namuna</div>
      </div>
      <pre class="rounded-2xl bg-[var(--p-elevated)] p-4 text-xs overflow-x-auto"><code>curl --request GET \
  --url '{{ url('/api/v1/client/products/books') }}' \
  --header 'Accept: application/json' \
  --header 'X-App-ID: app_xxxxxxxxxxxx' \
  --header 'X-App-Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'</code></pre>
    </section>

    <aside class="space-y-4 fade-up">
      <section class="p-card">
        <div class="dash-card-head">
          <div class="dash-card-title">Muhim eslatmalar</div>
        </div>
        <div class="space-y-3 text-sm text-[var(--p-hint)]">
          <p><code>products/{col}</code> route oxirida turadi. Shu sabab maxsus endpointlar (`sellers/list`, `recommendation/...`) normal ishlaydi.</p>
          <p>Nofaol mijozlar <code>403</code> oladi.</p>
          <p>Headerlar bo‘lmasa <code>401</code> qaytadi.</p>
          <p>Ability hozir <code>read</code> bilan tekshiriladi.</p>
          <p>Limitdan oshsa API <code>429</code> qaytaradi va <code>Retry-After</code> header beradi.</p>
          <p>Har bir so‘rov admin paneldagi audit logga yoziladi.</p>
        </div>
      </section>

      <section class="p-card">
        <div class="dash-card-head">
          <div class="dash-card-title">Response formati</div>
        </div>
        <pre class="rounded-2xl bg-[var(--p-elevated)] p-4 text-xs overflow-x-auto"><code>{
  "status": "success",
  "data": [...]
}</code></pre>
      </section>

      <section class="p-card">
        <div class="dash-card-head">
          <div class="dash-card-title">Rate limit headerlari</div>
        </div>
        <pre class="rounded-2xl bg-[var(--p-elevated)] p-4 text-xs overflow-x-auto"><code>X-RateLimit-Limit-Second: 8
X-RateLimit-Limit-Minute: 240
X-RateLimit-Remaining-Second: 7
X-RateLimit-Remaining-Minute: 239
Retry-After: 1</code></pre>
      </section>
    </aside>
  </div>
</div>
@endsection
