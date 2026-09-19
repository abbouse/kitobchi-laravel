@props(['endpoint'])

@php
    $ep       = $endpoint;
    $samples  = $ep['samples'] ?? [];
    $uid      = $ep['id'] ?? \Illuminate\Support\Str::random(6);
    $isWrite  = strtoupper($ep['method']) !== 'GET';
    $ability  = $ep['ability'] ?? 'read';
    $hasTry   = strtoupper($ep['method']) === 'GET';

    $hasPathParams  = ! empty($ep['path_params']);
    $hasQueryParams = ! empty($ep['query_params']);
    $hasBodyParams  = ! empty($ep['body_params']);
    $hasParams      = $hasPathParams || $hasQueryParams || $hasBodyParams;
@endphp

<div class="ep-card" id="{{ $uid }}">

  {{-- ── Card Header (method + path + tags) ──────────────────────────── --}}
  <div class="ep-header">
    <span class="method-badge method-{{ $ep['method'] }}">{{ $ep['method'] }}</span>
    <span class="ep-path">{{ $ep['full_path'] ?? $ep['path'] }}</span>
    <div class="ep-meta">
      @if(! empty($ep['cache']))
        <span class="ep-tag cached">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;margin-right:2px"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
          Kesh
        </span>
      @endif
      <span class="ep-tag {{ $ability === 'stock:write' ? 'write' : '' }}">{{ $ability }}</span>
    </div>
  </div>

  {{-- ── Card Body ─────────────────────────────────────────────────────── --}}
  <div class="ep-body">

    {{-- Title & Summary --}}
    @if(! empty($ep['title']))
      <div class="ep-title">{{ $ep['title'] }}</div>
    @endif
    @if(! empty($ep['summary']))
      <p class="ep-summary">{!! $ep['summary'] !!}</p>
    @endif

    {{-- ── Parameters Table ──────────────────────────────────────────── --}}
    @if($hasParams)
      <table class="doc-table">
        <thead>
          <tr>
            <th>Parametr</th>
            <th>Turi</th>
            <th>Joyi</th>
            <th>Majburiy</th>
            <th>Tavsifi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($ep['path_params'] ?? [] as $p)
            <tr>
              <td><span class="ic">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td><span style="color:var(--faint);font-size:12px;font-weight:600">path</span></td>
              <td><strong style="color:var(--text)">Ha</strong></td>
              <td>{!! $p['desc'] ?? '' !!}</td>
            </tr>
          @endforeach
          @foreach($ep['query_params'] ?? [] as $p)
            <tr>
              <td><span class="ic">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td><span style="color:var(--faint);font-size:12px;font-weight:600">query</span></td>
              <td>{{ ($p['required'] ?? false) ? 'Ha' : 'Yo\'q' }}</td>
              <td>{!! $p['desc'] ?? '' !!}</td>
            </tr>
          @endforeach
          @foreach($ep['body_params'] ?? [] as $p)
            <tr>
              <td><span class="ic">{{ $p['name'] }}</span></td>
              <td><span class="type-chip">{{ $p['type'] ?? 'string' }}</span></td>
              <td><span style="color:var(--faint);font-size:12px;font-weight:600">body</span></td>
              <td>{{ ($p['required'] ?? false) ? 'Ha' : 'Yo\'q' }}</td>
              <td>{!! $p['desc'] ?? '' !!}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    {{-- ── Request Body (for POST/PUT) ──────────────────────────────── --}}
    @if(! empty($ep['request_json']))
      <div style="margin: 14px 0 4px; font-size: 12.5px; font-weight: 700; color: var(--text2); display: flex; align-items: center; gap: 8px;">
        So'rov tanasi (Body)
        <span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:99px;background:rgba(79,131,247,0.1);color:var(--accent)">JSON</span>
      </div>
      <div class="code-block">
        <div class="code-block-head">
          <span class="code-block-lang">json</span>
          <button class="code-copy-btn" type="button">Nusxalash</button>
        </div>
        <pre>{{ $ep['request_json'] }}</pre>
      </div>
    @endif

    {{-- ── Code Samples ───────────────────────────────────────────────── --}}
    @if(! empty($samples))
      <div class="code-tabs">
        <div class="code-tabs-bar">
          <div class="code-tabs-langs">
            @foreach($samples as $lang => $sample)
              <button type="button" class="ctab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $uid }}-{{ $lang }}">
                {{ $sample['label'] }}
              </button>
            @endforeach
          </div>
          <button type="button" class="ctab-copy">Nusxalash</button>
        </div>
        @foreach($samples as $lang => $sample)
          <div class="code-pane {{ $loop->first ? 'active' : '' }}" data-pane="{{ $uid }}-{{ $lang }}">
            <pre>{{ $sample['code'] }}</pre>
          </div>
        @endforeach
      </div>
    @endif

    {{-- ── Response Preview ───────────────────────────────────────────── --}}
    @if(! empty($ep['response_json']))
      <div class="ep-response-head">
        <span class="ep-response-label">
          Javob namunasi
          <span class="response-200">200 OK</span>
        </span>
        <button class="code-copy-btn" style="font-size:11px;padding:3px 9px;background:var(--bg2);color:var(--muted);border:1px solid var(--border);border-radius:6px;cursor:pointer;" type="button" data-resp-copy>Nusxalash</button>
      </div>
      <div class="code-block ep-response" data-resp-block>
        <pre><code>{{ $ep['response_json'] }}</code></pre>
      </div>
    @endif

    {{-- ── Try It Out (GET only) ──────────────────────────────────────── --}}
    @if($hasTry)
      <details
        class="try-console"
        data-tryit
        data-url-template="{{ $ep['url_template'] ?? $ep['full_path'] ?? '' }}"
      >
        <summary class="try-toggle">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          Konsolda sinab ko'rish (Live)
        </summary>
        <div class="try-body">
          <div class="try-fields">
            <div class="try-field">
              <label class="try-label">X-App-ID</label>
              <input class="try-input" data-try="appid" placeholder="app_xxxxxxxxxxxx" autocomplete="off" spellcheck="false">
            </div>
            <div class="try-field">
              <label class="try-label">X-App-Secret</label>
              <input class="try-input" data-try="secret" type="password" placeholder="your-secret" autocomplete="off">
            </div>
            @foreach($ep['path_params'] ?? [] as $p)
              <div class="try-field">
                <label class="try-label">{{ $p['name'] }}</label>
                <input class="try-input" data-try-path="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['type'] ?? 'string' }}">
              </div>
            @endforeach
            @foreach($ep['query_params'] ?? [] as $p)
              <div class="try-field">
                <label class="try-label">
                  {{ $p['name'] }}
                  @if($p['required'] ?? false)<span class="req">*</span>@endif
                </label>
                <input class="try-input" data-try-query="{{ $p['name'] }}" value="{{ $p['example'] ?? '' }}" placeholder="{{ $p['type'] ?? 'string' }}">
              </div>
            @endforeach
          </div>
          <div class="try-actions">
            <button type="button" class="try-send-btn" data-try-send>
              So'rov yuborish →
            </button>
            <span class="try-status" data-try-status></span>
          </div>
          <pre class="try-result" data-try-result hidden></pre>
        </div>
      </details>
    @endif

  </div>
</div>

<style>
/* response copy btn hover */
[data-resp-copy]:hover { background: var(--bg) !important; color: var(--text) !important; }
[data-resp-copy].copied { color: #22c55e !important; border-color: rgba(34,197,94,.4) !important; }
</style>
<script>
/* response copy */
document.addEventListener('click', async function(e) {
  const btn = e.target.closest('[data-resp-copy]');
  if (!btn) return;
  const block = btn.closest('.ep-response-head')?.nextElementSibling;
  const code = block?.querySelector('code');
  if (!code) return;
  try {
    await navigator.clipboard.writeText(code.innerText);
    const orig = btn.textContent;
    btn.classList.add('copied'); btn.textContent = '✓ Nusxalandi';
    setTimeout(() => { btn.classList.remove('copied'); btn.textContent = orig; }, 1400);
  } catch(_) {}
}, { once: false });
</script>
