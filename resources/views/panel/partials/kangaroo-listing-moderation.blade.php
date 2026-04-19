@php
    $issues = is_array($model->kangaroo_listing_issues ?? null) ? $model->kangaroo_listing_issues : [];
    $hasK = $model->kangaroo_listing_checked_at
        || filled($model->kangaroo_listing_decision)
        || $model->kangaroo_listing_score !== null
        || count($issues) > 0;
    $decLbl = match ($model->kangaroo_listing_decision) {
        'approve' => 'Tavsiya: tasdiqlash',
        'reject' => 'Tavsiya: rad',
        'human_review' => 'Inson ko‘rib chiqishi kerak',
        default => $model->kangaroo_listing_decision ?: '—',
    };
@endphp
@if($hasK)
<div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--p-border)">
    <div style="font-size:10px;color:var(--p-hint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Kangaroo (avtomatik)</div>
    <div style="font-size:13px;color:var(--p-text);line-height:1.5">
        <div><span style="color:var(--p-hint)">Qaror:</span> <strong>{{ $decLbl }}</strong></div>
        @if($model->kangaroo_listing_score !== null)
            <div><span style="color:var(--p-hint)">Ball:</span> <strong>{{ (int) $model->kangaroo_listing_score }}</strong> / 100</div>
        @endif
        @if($model->kangaroo_listing_checked_at)
            <div style="font-size:12px;color:var(--p-hint);margin-top:4px">Tekshirilgan: {{ $model->kangaroo_listing_checked_at->format('d.m.Y H:i') }}</div>
        @endif
        @if(count($issues))
            <div style="margin-top:8px;font-size:12px;color:var(--p-muted)">
                <span style="color:var(--p-hint)">Izohlar:</span>
                <ul style="margin:4px 0 0 16px;padding:0">
                    @foreach($issues as $it)
                        @php
                            $code = is_array($it) ? ($it['code'] ?? json_encode($it)) : (string) $it;
                        @endphp
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endif
