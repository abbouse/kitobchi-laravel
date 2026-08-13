@extends('layouts.marketplace')

@section('title', 'Mening profilim — Kitobchi')

@section('content')
<div style="min-height:80dvh;padding:2rem 0;background:#f8fafc;">
    <div style="width:100%;max-width:var(--ui-container);margin:0 auto;padding:0 1rem;">
        
        <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;" id="kcProfileGrid">
            <!-- User Info Card -->
            <div style="background:#fff;border-radius:1.25rem;padding:1.5rem;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
                    <div style="width:4.5rem;height:4.5rem;border-radius:9999px;background:var(--color-tima-100);color:var(--color-tima-500);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;flex-shrink:0;">
                        {{ strtoupper(substr($user->name ?: $user->phone_number, 0, 1)) }}
                    </div>
                    <div>
                        <h2 style="font-size:1.25rem;font-weight:800;color:#0f172a;margin:0 0 0.25rem;">
                            {{ $user->name ?: 'Foydalanuvchi' }}
                        </h2>
                        <p style="color:#64748b;font-size:0.875rem;margin:0;">
                            📱 +{{ $user->phone_number }}
                        </p>
                    </div>
                </div>

                <hr style="border:0;border-top:1px solid #f1f5f9;margin:1.25rem 0;">

                <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    <form action="{{ route('web.auth.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" 
                                style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.75rem 1.25rem;background:#fef2f2;color:#ef4444;border:none;border-radius:9999px;font-weight:700;font-size:0.875rem;cursor:pointer;transition:all 0.2s;"
                                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Tizimdan chiqish
                        </button>
                    </form>
                </div>
            </div>

            <!-- Orders History -->
            <div style="background:#fff;border-radius:1.25rem;padding:1.5rem;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:1.125rem;font-weight:800;color:#0f172a;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem;">
                    <span>📦</span> Mening buyurtmalarim ({{ count($orders) }})
                </h3>

                @if(count($orders) === 0)
                    <div style="text-align:center;padding:3rem 1rem;">
                        <div style="font-size:3.5rem;margin-bottom:1rem;">🛍️</div>
                        <h4 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.5rem;">Sizda hozircha buyurtmalar yo'q</h4>
                        <p style="color:#64748b;font-size:0.875rem;margin:0 0 1.5rem;">Katalogdan o'zingizga yoqqan kitoblarni xarid qilishingiz mumkin.</p>
                        <a href="{{ route('web.catalog') }}" 
                           style="display:inline-flex;align-items:center;padding:0.75rem 1.75rem;background:var(--color-tima-500);color:#fff;border-radius:9999px;font-weight:700;text-decoration:none;font-size:0.875rem;">
                            Katalogga o'tish
                        </a>
                    </div>
                @else
                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        @foreach($orders as $order)
                            <div style="padding:1.25rem;border:1px solid #f1f5f9;border-radius:0.875rem;background:#fafafa;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">
                                    <div>
                                        <span style="font-weight:800;color:#0f172a;">Buyurtma #{{ $order->order_number ?? $order->id }}</span>
                                        <span style="color:#64748b;font-size:0.8125rem;margin-left:0.5rem;">
                                            {{ optional($order->created_at)->format('d.m.Y, H:i') }}
                                        </span>
                                    </div>
                                    <span style="padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:700;
                                        @if($order->paymentStatus == 2) background:#dcfce7;color:#15803d;
                                        @elseif($order->paymentStatus == 1) background:#fef9c3;color:#a16207;
                                        @else background:#f1f5f9;color:#475569; @endif">
                                        @if($order->paymentStatus == 2) Muvaffaqiyatli to'langan
                                        @elseif($order->paymentStatus == 1) Kutilmoqda
                                        @else Qabul qilindi @endif
                                    </span>
                                </div>

                                <div style="font-size:0.875rem;color:#334155;margin-bottom:0.75rem;">
                                    <strong>Manzil:</strong> {{ $order->address ?? $order->city ?? 'Belgilanmagan' }}
                                </div>

                                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e2e8f0;padding-top:0.75rem;">
                                    <span style="font-size:0.875rem;color:#64748b;">Jami summa:</span>
                                    <span style="font-size:1.125rem;font-weight:800;color:var(--color-tima-500);">
                                        {{ number_format($order->summa ?? $order->price ?? 0, 0, ',', ' ') }} so'm
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
window.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('kcProfileGrid');
    if (grid && window.innerWidth >= 768) {
        grid.style.gridTemplateColumns = '300px 1fr';
    }
});
</script>
@endpush
