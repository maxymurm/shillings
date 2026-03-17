<x-filament-panels::page>
<style>
/* ── Account Register: dark-mode-safe base classes ───────── */
.ar-page          { }
.ar-search-wrap   { background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;gap:10px;align-items:center; }
.dark .ar-search-wrap { background:#1e293b;border-color:#374151; }

.ar-sec-header    { border-radius:8px 8px 0 0;padding:10px 16px;display:flex;align-items:center;justify-content:space-between; }
.ar-table-wrap    { border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;overflow:hidden; }
.dark .ar-table-wrap { border-color:#334155; }

.ar-row           { border-bottom:1px solid #f3f4f6;transition:background .1s; }
.dark .ar-row     { border-bottom-color:#1e293b; }
.ar-row-even      { background:#ffffff; }
.dark .ar-row-even{ background:#1e293b; }
.ar-row-odd       { background:#f9fafb; }
.dark .ar-row-odd { background:#172032; }
.ar-row:hover     { background:#ecfdf5 !important; }
.dark .ar-row:hover{ background:#064e3b !important; }
.ar-row-void      { opacity:.5; }

.ar-text-main     { color:#111827; }
.dark .ar-text-main{ color:#f1f5f9; }
.ar-text-sub      { color:#6b7280; }
.dark .ar-text-sub { color:#94a3b8; }
.ar-text-muted    { color:#d1d5db; }
.dark .ar-text-muted{ color:#475569; }

.ar-code-badge    { font-size:11px;font-family:monospace;background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:4px;padding:2px 6px; }
.dark .ar-code-badge{ background:#334155;color:#94a3b8;border-color:#475569; }

.ar-input         { border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 8px;font-size:12px;outline:none;width:100%; }
.dark .ar-input   { border-color:#475569;background:#1e293b;color:#f1f5f9; }
.ar-input:focus   { border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.15); }

.ar-input-debit   { border:1px solid #bfdbfe;border-radius:5px;background:#eff6ff;color:#1d4ed8;padding:4px 8px;font-size:12px;font-family:monospace;text-align:right;outline:none;width:100%; }
.dark .ar-input-debit{ border-color:#1e40af;background:#172554;color:#93c5fd; }
.ar-input-debit:focus{ border-color:#3b82f6; }

.ar-input-credit  { border:1px solid #fed7aa;border-radius:5px;background:#fff7ed;color:#c2410c;padding:4px 8px;font-size:12px;font-family:monospace;text-align:right;outline:none;width:100%; }
.dark .ar-input-credit{ border-color:#9a3412;background:#431407;color:#fb923c; }
.ar-input-credit:focus{ border-color:#f97316; }

.ar-select        { border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 8px;font-size:12px;outline:none;width:100%; }
.dark .ar-select  { border-color:#475569;background:#1e293b;color:#f1f5f9; }

.ar-btn-save      { background:#059669;color:#fff;font-size:12px;font-weight:600;padding:5px 12px;border-radius:5px;border:none;cursor:pointer;white-space:nowrap; }
.ar-btn-save:hover{ background:#047857; }

.ar-btn-action    { background:none;border:none;cursor:pointer;padding:3px;border-radius:4px;font-size:13px;opacity:.5;transition:opacity .15s; }
.ar-btn-action:hover{ opacity:1; }

.ar-th            { background:#f3f4f6;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:9px 8px;cursor:pointer;user-select:none;white-space:nowrap; }
.dark .ar-th      { background:#172032;color:#94a3b8; }
.ar-th:hover      { background:#e5e7eb; }
.dark .ar-th:hover{ background:#1e293b; }
.ar-th-active     { color:#10b981 !important; }

.ar-entry-row     { background:#f0fdf4;border-bottom:2px solid #10b981; }
.dark .ar-entry-row{ background:#052e16;border-bottom-color:#059669; }

.ar-filter-bar    { background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:14px; }
.dark .ar-filter-bar{ background:#1e293b;border-color:#334155; }

.ar-preset-btn    { font-size:11px;padding:3px 10px;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#6b7280;cursor:pointer;white-space:nowrap; }
.dark .ar-preset-btn{ background:#1e293b;border-color:#475569;color:#94a3b8; }
.ar-preset-btn:hover,.ar-preset-btn-active{ background:#10b981;border-color:#10b981;color:#fff !important; }

.ar-acct-header   { border-radius:10px;padding:18px 22px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px; }

.ar-suggestions   { position:absolute;z-index:50;left:0;top:100%;margin-top:2px;width:100%;min-width:280px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow:hidden; }
.dark .ar-suggestions{ background:#1e293b;border-color:#334155;box-shadow:0 8px 24px rgba(0,0,0,.4); }
.ar-sug-hdr       { font-size:11px;color:#9ca3af;padding:5px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb; }
.dark .ar-sug-hdr { background:#172032;border-bottom-color:#334155; }
.ar-sug-row       { padding:8px 12px;border-bottom:1px solid #f3f4f6;cursor:pointer; }
.dark .ar-sug-row { border-bottom-color:#334155; }
.ar-sug-row:hover,.ar-sug-focused{ background:#f0fdf4; }
.dark .ar-sug-row:hover,.dark .ar-sug-focused{ background:#064e3b; }

.ar-footer-row    { background:#f9fafb;font-size:12px;font-weight:700;border-top:2px solid #e5e7eb; }
.dark .ar-footer-row{ background:#172032;border-top-color:#334155; }

.ar-kbd           { font-family:monospace;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;padding:1px 6px;font-size:11px; }
.dark .ar-kbd     { background:#334155;border-color:#475569;color:#e2e8f0; }

.ar-void-badge    { font-size:10px;font-weight:700;letter-spacing:.04em;color:#ef4444;background:#fef2f2;border:1px solid #fecaca;border-radius:999px;padding:1px 7px;margin-left:6px; }
.dark .ar-void-badge{ background:#450a0a;border-color:#991b1b;color:#fca5a5; }
.ar-posted-badge  { font-size:10px;font-weight:700;letter-spacing:.04em;color:#059669;background:#d1fae5;border-radius:999px;padding:1px 7px;margin-left:6px; }
.dark .ar-posted-badge{ background:#022c22;color:#6ee7b7; }

.ar-empty         { text-align:center;padding:48px 20px; }
.ar-empty, .dark .ar-empty { color:#9ca3af; }

.ar-back-btn      { display:inline-flex;align-items:center;gap:6px;font-size:13px;background:none;border:1px solid #e5e7eb;border-radius:6px;padding:5px 12px;cursor:pointer;color:#6b7280;transition:all .15s; }
.dark .ar-back-btn{ border-color:#334155;color:#94a3b8; }
.ar-back-btn:hover{ color:#10b981;border-color:#10b981; }
</style>

@php
$typeConfig = [
    'ASSET'     => ['color' => '#3b82f6', 'light' => 'rgba(59,130,246,.12)',  'icon' => '💵', 'label' => 'Assets'],
    'LIABILITY' => ['color' => '#f97316', 'light' => 'rgba(249,115,22,.12)',  'icon' => '💳', 'label' => 'Liabilities'],
    'EQUITY'    => ['color' => '#8b5cf6', 'light' => 'rgba(139,92,246,.12)',  'icon' => '🏦', 'label' => 'Equity'],
    'INCOME'    => ['color' => '#10b981', 'light' => 'rgba(16,185,129,.12)',  'icon' => '📈', 'label' => 'Income'],
    'EXPENSE'   => ['color' => '#ef4444', 'light' => 'rgba(239,68,68,.12)',   'icon' => '📉', 'label' => 'Expenses'],
];
@endphp

<div
    x-data="{
        suggestionsOpen: false,
        focusedSuggestion: -1,
        accountSearch: '',
        collapsed: {},
        openSuggestions()  { this.suggestionsOpen = this.$wire.suggestions.length > 0; },
        closeSuggestions() { this.suggestionsOpen = false; this.focusedSuggestion = -1; },
        arrowDown() { if (!this.suggestionsOpen) return; this.focusedSuggestion = Math.min(this.focusedSuggestion + 1, this.$wire.suggestions.length - 1); },
        arrowUp()   { if (!this.suggestionsOpen) return; this.focusedSuggestion = Math.max(this.focusedSuggestion - 1, 0); },
        acceptFocused() { if (this.focusedSuggestion >= 0 && this.suggestionsOpen) { this.$wire.applySuggestion(this.focusedSuggestion); this.closeSuggestions(); } },
        acceptFirst()   { if (this.suggestionsOpen && this.$wire.suggestions.length > 0) { this.$wire.applySuggestion(0); this.closeSuggestions(); } },
        toggleCollapse(type) { this.collapsed[type] = !this.collapsed[type]; },
        isCollapsed(type)    { return !!this.collapsed[type]; },
        matchesSearch(name, code)   { if (!this.accountSearch) return true; const q = this.accountSearch.toLowerCase(); return name.toLowerCase().includes(q) || (code && code.toLowerCase().includes(q)); }
    }"
    @click.outside="closeSuggestions()"
    class="ar-page"
>

@if(!$this->accountId)

{{-- ══════════════════════════════════════════════
     ACCOUNTS LIST (landing view)
══════════════════════════════════════════════ --}}
@php
    $summaries   = $this->getAccountSummaries();
    $totalAccts  = collect($summaries)->flatten(1)->count();
@endphp

{{-- Search bar --}}
<div class="ar-search-wrap">
    <svg style="width:16px;height:16px;flex-shrink:0;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
    </svg>
    <input
        type="text"
        x-model="accountSearch"
        placeholder="Search accounts by name or code…"
        class="ar-input"
        style="border:none;background:transparent;padding:0;flex:1;font-size:13px;"
    />
    <span class="ar-text-sub" style="font-size:12px;white-space:nowrap;">{{ $totalAccts }} accounts</span>
</div>

@forelse($summaries as $typeName => $typeAccounts)
@php
    $cfg   = $typeConfig[$typeName] ?? ['color' => '#6b7280', 'light' => 'rgba(107,114,128,.12)', 'icon' => '📋', 'label' => $typeName];
    $color = $cfg['color'];
    $light = $cfg['light'];
    $icon  = $cfg['icon'];
    $label = $cfg['label'];

    $typeTotal = collect($typeAccounts)->sum(fn($r) =>
        $r['balance_neg']
            ? -(float) str_replace(',', '', $r['balance'])
            :  (float) str_replace(',', '', $r['balance'])
    );
    $typeTotalNeg = $typeTotal < 0;
    $typeTotalFmt = number_format(abs($typeTotal), 2);
@endphp

<div style="margin-bottom:20px;" x-show="
    !accountSearch ||
    {{ collect($typeAccounts)->map(fn($r) => 'matchesSearch(' . json_encode($r['name']) . ',' . json_encode($r['code'] ?? '') . ')')->join(' || ') }}
">

    {{-- Section header (clickable to collapse) --}}
    <div
        class="ar-sec-header"
        style="background:{{ $light }};border-left:4px solid {{ $color }};cursor:pointer;"
        @click="toggleCollapse('{{ $typeName }}')"
    >
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;line-height:1;">{{ $icon }}</span>
            <span style="font-weight:700;font-size:13px;color:{{ $color }};letter-spacing:.05em;text-transform:uppercase;">
                {{ $label }}
            </span>
            <span style="font-size:11px;background:{{ $color }};color:#fff;border-radius:999px;padding:1px 8px;font-weight:600;">
                {{ count($typeAccounts) }}
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:14px;">
            <span style="font-size:13px;font-family:monospace;font-weight:700;
                         color:{{ $typeTotalNeg ? '#ef4444' : $color }};">
                {{ $typeTotalNeg ? '−' : '' }}{{ $typeTotalFmt }}
            </span>
            {{-- Collapse chevron --}}
            <svg
                style="width:14px;height:14px;color:{{ $color }};transition:transform .2s;"
                :style="isCollapsed('{{ $typeName }}') ? 'transform:rotate(-90deg)' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    {{-- Accounts table (collapsible) --}}
    <div class="ar-table-wrap" x-show="!isCollapsed('{{ $typeName }}')" x-collapse>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tbody>
            @foreach($typeAccounts as $idx => $row)
            <tr
                wire:key="acct-{{ $row['id'] }}"
                wire:click="$set('accountId', '{{ $row['id'] }}')"
                class="ar-row {{ $idx % 2 === 0 ? 'ar-row-even' : 'ar-row-odd' }}"
                style="cursor:pointer;"
                x-show="matchesSearch({{ json_encode($row['name']) }}, {{ json_encode($row['code'] ?? '') }})"
            >
                {{-- Code badge --}}
                <td style="padding:10px 8px 10px 16px;width:1%;white-space:nowrap;">
                    @if($row['code'])
                        <span class="ar-code-badge">{{ $row['code'] }}</span>
                    @endif
                </td>

                {{-- Name --}}
                <td style="padding:10px 8px;">
                    <span class="ar-text-main" style="font-weight:600;">{{ $row['name'] }}</span>
                </td>

                {{-- Currency --}}
                <td style="padding:10px 8px;white-space:nowrap;text-align:right;">
                    @if($row['currency'])
                        <span class="ar-code-badge" style="font-size:10px;">{{ $row['currency'] }}</span>
                    @endif
                </td>

                {{-- Tx count chip --}}
                <td style="padding:10px 8px;white-space:nowrap;text-align:right;">
                    @if($row['tx_count'] > 0)
                        <span class="ar-code-badge">{{ $row['tx_count'] }} txn</span>
                    @else
                        <span class="ar-text-muted" style="font-size:11px;">no transactions</span>
                    @endif
                </td>

                {{-- Balance --}}
                <td style="padding:10px 16px 10px 8px;text-align:right;white-space:nowrap;
                           font-family:monospace;font-weight:700;font-size:13px;
                           color:{{ $row['balance_neg'] ? '#ef4444' : $color }};">
                    {{ $row['balance_neg'] ? '−' : '' }}{{ $row['balance'] }}
                </td>

                {{-- Chevron --}}
                <td style="padding:10px 12px 10px 0;text-align:right;width:1%;white-space:nowrap;">
                    <svg style="width:14px;height:14px;display:inline;opacity:.4;" fill="none" stroke="{{ $color }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@empty
<div class="ar-empty">
    <div style="font-size:40px;margin-bottom:12px;">📂</div>
    <div style="font-size:16px;font-weight:600;margin-bottom:4px;" class="ar-text-main">No accounts found</div>
    <div style="font-size:13px;" class="ar-text-sub">Go to the Accounts section to set up your chart of accounts.</div>
</div>
@endforelse

@else

{{-- ══════════════════════════════════════════════
     REGISTER HEADER (back button + account card)
══════════════════════════════════════════════ --}}
@php
    $acct     = $this->getCurrentAccount();
    $typeName = strtoupper($acct?->accountType?->name ?? 'ASSET');
    $cfg      = $typeConfig[$typeName] ?? ['color' => '#6b7280', 'light' => 'rgba(107,114,128,.12)', 'icon' => '📋', 'label' => $typeName];
    $hColor   = $cfg['color'];
    $hLight   = $cfg['light'];
    $hIcon    = $cfg['icon'];
    $hLabel   = $cfg['label'];
    $stats    = $this->getCurrentAccountStats();
@endphp

{{-- Back button --}}
<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <button wire:click="$set('accountId', '')" class="ar-back-btn">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        All Accounts
    </button>
    {{-- Export CSV --}}
    <button wire:click="exportCsv" class="ar-preset-btn" style="display:flex;align-items:center;gap:5px;">
        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </button>
</div>

{{-- Account header card --}}
<div class="ar-acct-header" style="border-left:5px solid {{ $hColor }};background:{{ $hLight }};">
    <div style="display:flex;align-items:center;gap:14px;">
        <span style="font-size:32px;line-height:1;">{{ $hIcon }}</span>
        <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if($acct?->code)
                    <span class="ar-code-badge">{{ $acct->code }}</span>
                @endif
                <span class="ar-text-main" style="font-size:18px;font-weight:800;">{{ $acct?->name }}</span>
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
                             color:{{ $hColor }};border:1px solid {{ $hColor }};
                             border-radius:999px;padding:2px 9px;background:{{ $hLight }};">
                    {{ $hLabel }}
                </span>
                @if($acct?->currency)
                    <span class="ar-code-badge" style="font-size:10px;">{{ $acct->currency->code }}</span>
                @endif
            </div>
            @if($acct?->description)
                <div class="ar-text-sub" style="font-size:12px;margin-top:4px;">{{ $acct->description }}</div>
            @endif
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:26px;font-family:monospace;font-weight:800;
                    color:{{ $stats['balance_neg'] ? '#ef4444' : $hColor }};">
            {{ $stats['balance_neg'] ? '−' : '' }}{{ $stats['balance'] }}
        </div>
        <div style="display:flex;gap:18px;margin-top:6px;font-size:11px;flex-wrap:wrap;justify-content:flex-end;">
            <span class="ar-text-sub"><strong class="ar-text-main">{{ $stats['tx_count'] }}</strong> txn</span>
            <span>Dr: <strong style="color:#3b82f6;">{{ $stats['total_debit'] }}</strong></span>
            <span>Cr: <strong style="color:#f97316;">{{ $stats['total_credit'] }}</strong></span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     FILTER BAR
══════════════════════════════════════════════ --}}
<div class="ar-filter-bar">
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">

        {{-- Search --}}
        <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:180px;">
            <svg style="width:13px;height:13px;flex-shrink:0;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="registerSearch" placeholder="Search transactions…" class="ar-input" style="min-width:160px;flex:1;" />
        </div>

        {{-- Date from --}}
        <input type="date" wire:model.live="registerStartDate" class="ar-input" style="width:140px;" title="From date" />
        <input type="date" wire:model.live="registerEndDate"   class="ar-input" style="width:140px;" title="To date" />

        {{-- Unreconciled toggle --}}
        <label style="display:flex;align-items:center;gap:5px;cursor:pointer;white-space:nowrap;" class="ar-text-sub" style="font-size:12px;">
            <input type="checkbox" wire:model.live="registerUnreconciledOnly" style="accent-color:#10b981;" />
            Unreconciled only
        </label>

        {{-- Preset buttons --}}
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <button wire:click="setDatePreset('today')"      class="ar-preset-btn">Today</button>
            <button wire:click="setDatePreset('this_week')"  class="ar-preset-btn">This Week</button>
            <button wire:click="setDatePreset('this_month')" class="ar-preset-btn">This Month</button>
            <button wire:click="setDatePreset('this_year')"  class="ar-preset-btn">This Year</button>
            <button wire:click="setDatePreset('all')"        class="ar-preset-btn">All</button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     REGISTER TABLE
══════════════════════════════════════════════ --}}
@php $rows = $this->getRegisterRows(); @endphp
<div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);" class="ar-table-wrap" style="">
<table style="width:100%;border-collapse:collapse;font-size:13px;">
    <thead>
        <tr>
            {{-- Reconcile: not sortable --}}
            <th class="ar-th" style="padding:9px 8px;width:36px;text-align:center;cursor:default;" title="Reconciliation status — click a row's status to cycle n→c→y">R</th>
            {{-- Sortable headers --}}
            @php
            $sortIcon = fn($col) => $this->registerSortColumn === $col
                ? ($this->registerSortDir === 'asc' ? ' ▲' : ' ▼')
                : ' ↕';
            @endphp
            <th class="ar-th {{ $this->registerSortColumn === 'date' ? 'ar-th-active' : '' }}" wire:click="sortBy('date')" style="width:100px;">Date{{ $sortIcon('date') }}</th>
            <th class="ar-th" style="width:80px;cursor:default;">Num</th>
            <th class="ar-th {{ $this->registerSortColumn === 'description' ? 'ar-th-active' : '' }}" wire:click="sortBy('description')">Description{{ $sortIcon('description') }}</th>
            <th class="ar-th" style="cursor:default;">Transfer Account</th>
            <th class="ar-th {{ $this->registerSortColumn === 'debit' ? 'ar-th-active' : '' }}" wire:click="sortBy('debit')" style="text-align:right;width:110px;color:#3b82f6;">Debit{{ $sortIcon('debit') }}</th>
            <th class="ar-th {{ $this->registerSortColumn === 'credit' ? 'ar-th-active' : '' }}" wire:click="sortBy('credit')" style="text-align:right;width:110px;color:#f97316;">Credit{{ $sortIcon('credit') }}</th>
            <th class="ar-th {{ $this->registerSortColumn === 'balance' ? 'ar-th-active' : '' }}" wire:click="sortBy('balance')" style="text-align:right;width:120px;">Balance{{ $sortIcon('balance') }}</th>
            <th class="ar-th" style="width:90px;cursor:default;text-align:center;">Actions</th>
        </tr>
    </thead>

    {{-- ── QUICK ENTRY ROW ── --}}
    <tbody>
    <tr class="ar-entry-row">
        <td style="padding:6px 8px;text-align:center;font-weight:700;color:#10b981;">+</td>

        {{-- Date --}}
        <td style="padding:4px;">
            <input type="date" wire:model="entryDate" class="ar-input" />
        </td>

        {{-- Num --}}
        <td style="padding:4px;">
            <input type="text" wire:model="entryNum" placeholder="Ref#" class="ar-input" />
        </td>

        {{-- Description with autocomplete --}}
        <td style="padding:4px;position:relative;">
            <input
                type="text"
                wire:model.live.debounce.400ms="entryDescription"
                placeholder="Description (type for suggestions)…"
                autocomplete="off"
                @focus="openSuggestions()"
                @input="$wire.suggestions.length > 0 ? suggestionsOpen = true : suggestionsOpen = false"
                @keydown.arrow-down.prevent="arrowDown()"
                @keydown.arrow-up.prevent="arrowUp()"
                @keydown.enter.prevent="if (suggestionsOpen) { acceptFocused() } else { $wire.saveEntry() }"
                @keydown.tab="acceptFirst(); $nextTick(() => suggestionsOpen = false)"
                @keydown.escape="closeSuggestions()"
                class="ar-input"
            />
            {{-- Suggestions dropdown --}}
            <div x-show="suggestionsOpen && $wire.suggestions.length > 0" x-transition class="ar-suggestions">
                <div class="ar-sug-hdr">History suggestions — Tab or ↵ to fill</div>
                <template x-for="(s, i) in $wire.suggestions" :key="i">
                    <div
                        @click="$wire.applySuggestion(i); closeSuggestions()"
                        @mouseenter="focusedSuggestion = i"
                        class="ar-sug-row"
                        :class="focusedSuggestion === i ? 'ar-sug-focused' : ''"
                    >
                        <div class="ar-text-main" style="font-weight:600;font-size:12px;" x-text="s.description"></div>
                        <div class="ar-text-sub" style="font-size:11px;margin-top:2px;display:flex;gap:10px;">
                            <span>→ <span x-text="s.transfer_account"></span></span>
                            <span x-show="s.debit"  style="color:#3b82f6;">Dr: <span x-text="s.debit"></span></span>
                            <span x-show="s.credit" style="color:#f97316;">Cr: <span x-text="s.credit"></span></span>
                        </div>
                    </div>
                </template>
            </div>
        </td>

        {{-- Transfer Account --}}
        <td style="padding:4px;">
            <select wire:model="entryTransferAccountId" class="ar-select">
                <option value="">— Account —</option>
                @php $group = null; @endphp
                @foreach($this->getTransferAccounts() as $ta)
                    @php $g = $ta->accountType?->name ?? 'Other'; @endphp
                    @if($g !== $group)
                        @if($group !== null)</optgroup>@endif
                        <optgroup label="{{ $g }}">
                        @php $group = $g; @endphp
                    @endif
                    <option value="{{ $ta->id }}" @selected($ta->id === $this->entryTransferAccountId)>
                        {{ $ta->code ? "[{$ta->code}] " : "" }}{{ $ta->name }}
                    </option>
                @endforeach
                @if($group !== null)</optgroup>@endif
            </select>
        </td>

        {{-- Debit input --}}
        <td style="padding:4px;">
            <input type="number" wire:model="entryDebit" placeholder="0.00" step="0.01" min="0"
                @input="if ($event.target.value) { $wire.set('entryCredit', '') }"
                class="ar-input-debit" />
        </td>

        {{-- Credit input --}}
        <td style="padding:4px;">
            <input type="number" wire:model="entryCredit" placeholder="0.00" step="0.01" min="0"
                @input="if ($event.target.value) { $wire.set('entryDebit', '') }"
                class="ar-input-credit" />
        </td>

        {{-- Balance placeholder --}}
        <td style="padding:6px 10px;text-align:right;font-size:12px;font-family:monospace;" class="ar-text-muted">—</td>

        {{-- Save button --}}
        <td style="padding:4px 8px;text-align:center;">
            <button wire:click="saveEntry" wire:loading.attr="disabled" class="ar-btn-save" title="Save (Enter)">
                <span wire:loading.remove wire:target="saveEntry">Save</span>
                <span wire:loading wire:target="saveEntry">…</span>
            </button>
        </td>
    </tr>

    {{-- ── REGISTER ROWS ── --}}
    @forelse($rows as $rowIdx => $row)
        <tr
            wire:key="row-{{ $row['split_id'] }}"
            class="ar-row {{ $rowIdx % 2 === 0 ? 'ar-row-even' : 'ar-row-odd' }} {{ $row['is_void'] ? 'ar-row-void' : '' }}"
            x-data="{ showActions: false }"
            @mouseenter="showActions = true"
            @mouseleave="showActions = false"
        >
            {{-- Inline reconcile toggle --}}
            <td style="padding:10px 8px;text-align:center;cursor:pointer;" wire:click="toggleReconcile({{ $row['split_id'] }})" title="Click to cycle: not reconciled → cleared → reconciled">
                @if($row['reconciled'] === 'y' || $row['reconciled'] === 'f')
                    <span style="color:#10b981;font-weight:700;" title="Reconciled — click to mark unreconciled">✓</span>
                @elseif($row['reconciled'] === 'c')
                    <span style="color:#60a5fa;font-weight:700;" title="Cleared — click to mark reconciled">c</span>
                @else
                    <span class="ar-text-muted" title="Not reconciled — click to mark cleared">n</span>
                @endif
            </td>

            {{-- Date --}}
            <td style="padding:10px 8px;font-size:12px;font-family:monospace;white-space:nowrap;" class="ar-text-sub">
                {{ $row['date'] }}
            </td>

            {{-- Num --}}
            <td style="padding:10px 8px;font-size:12px;font-family:monospace;" class="ar-text-muted">
                {{ $row['num'] }}
            </td>

            {{-- Description + memo --}}
            <td style="padding:10px 8px;max-width:240px;">
                <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" class="ar-text-main">
                    {{ $row['description'] }}
                    @if($row['is_void'])
                        <span class="ar-void-badge">VOID</span>
                    @elseif($row['is_posted'])
                        <span class="ar-posted-badge">POSTED</span>
                    @endif
                </div>
                @if($row['memo'])
                    <div style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;" class="ar-text-sub">
                        {{ $row['memo'] }}
                    </div>
                @endif
            </td>

            {{-- Transfer account --}}
            <td style="padding:10px 8px;font-size:12px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                       {{ $row['is_split'] ? 'font-style:italic;' : '' }}" class="ar-text-sub">
                @if($row['transfer_id'])
                    <button
                        wire:click="$set('accountId', '{{ $row['transfer_id'] }}')"
                        style="background:none;border:none;cursor:pointer;text-align:left;padding:0;font-size:12px;text-decoration:underline;text-underline-offset:2px;"
                        class="ar-text-sub"
                        title="Jump to {{ $row['transfer'] }}"
                    >{{ $row['transfer'] }}</button>
                @else
                    {{ $row['transfer'] }}
                @endif
            </td>

            {{-- Debit --}}
            <td style="padding:10px 8px;text-align:right;font-size:12px;font-family:monospace;
                       {{ $row['debit'] ? 'font-weight:700;color:#2563eb;' : '' }}">
                @if($row['debit'])
                    <span style="color:#2563eb;">{{ $row['debit'] }}</span>
                @else
                    <span class="ar-text-muted">—</span>
                @endif
            </td>

            {{-- Credit --}}
            <td style="padding:10px 8px;text-align:right;font-size:12px;font-family:monospace;">
                @if($row['credit'])
                    <span style="color:#ea580c;font-weight:700;">{{ $row['credit'] }}</span>
                @else
                    <span class="ar-text-muted">—</span>
                @endif
            </td>

            {{-- Running balance --}}
            <td style="padding:10px 8px;text-align:right;font-size:13px;font-family:monospace;font-weight:700;
                       color:{{ $row['balance_neg'] ? '#ef4444' : '#111827' }};" class="{{ $row['balance_neg'] ? '' : 'ar-text-main' }}">
                {{ $row['balance_neg'] ? '−' : '' }}{{ $row['balance'] }}
            </td>

            {{-- Actions (hover) --}}
            <td style="padding:6px 8px;text-align:center;white-space:nowrap;">
                <div x-show="showActions" style="display:flex;gap:2px;justify-content:center;">
                    {{-- Edit full transaction --}}
                    <a href="{{ route('filament.admin.resources.transactions.edit', $row['transaction_id']) }}"
                       wire:navigate
                       class="ar-btn-action" title="Edit transaction"
                       style="color:#6b7280;text-decoration:none;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    {{-- Duplicate --}}
                    <button wire:click="duplicateTransaction({{ $row['transaction_id'] }})" class="ar-btn-action" title="Duplicate to today" style="color:#8b5cf6;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    {{-- Void (only if not already voided or posted) --}}
                    @if(!$row['is_void'] && !$row['is_posted'])
                    <button wire:click="voidTransaction({{ $row['transaction_id'] }})" class="ar-btn-action" title="Void transaction" style="color:#f97316;"
                            onclick="return confirm('Void this transaction?')">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </button>
                    @endif
                    {{-- Delete (only if not posted) --}}
                    @if(!$row['is_posted'])
                    <button wire:click="deleteTransaction({{ $row['transaction_id'] }})" class="ar-btn-action" title="Delete transaction" style="color:#ef4444;"
                            onclick="return confirm('Permanently delete this transaction?')">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                </div>
                {{-- Always: just pencil when not hovering --}}
                <div x-show="!showActions">
                    <a href="{{ route('filament.admin.resources.transactions.edit', $row['transaction_id']) }}"
                       wire:navigate
                       class="ar-btn-action" title="Edit transaction" style="color:#6b7280;text-decoration:none;opacity:.3;">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" class="ar-empty">
                <div style="font-size:36px;margin-bottom:10px;">📂</div>
                <div style="font-size:15px;font-weight:600;margin-bottom:4px;" class="ar-text-main">No transactions yet</div>
                <div style="font-size:12px;" class="ar-text-sub">Use the entry row above to record the first transaction.</div>
            </td>
        </tr>
    @endforelse
    </tbody>

    {{-- Footer tally --}}
    @if(count($rows) > 0)
    @php
        $totalDebits  = collect($rows)->sum(fn($r) => (float) str_replace(',', '', $r['debit']));
        $totalCredits = collect($rows)->sum(fn($r) => (float) str_replace(',', '', $r['credit']));
        $lastRow      = $rows[count($rows) - 1]; // last row in current sort order = most recent shown
        $totalRows    = $this->getCurrentAccountStats()['tx_count'];
    @endphp
    <tfoot>
        <tr class="ar-footer-row">
            <td colspan="3" style="padding:10px 12px;" class="ar-text-sub">
                <span>{{ count($rows) }}{{ count($rows) < $totalRows ? ' of ' . $totalRows : '' }} {{ Str::plural('transaction', count($rows)) }}</span>
                @if($this->registerSearch || $this->registerStartDate || $this->registerEndDate || $this->registerUnreconciledOnly)
                    <span style="font-size:10px;background:#fef3c7;color:#92400e;border-radius:999px;padding:1px 7px;margin-left:6px;font-weight:600;border:1px solid #fcd34d;">FILTERED</span>
                @endif
            </td>
            <td colspan="2" style="padding:10px 8px;" class="ar-text-sub" style="font-size:11px;"></td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;color:#2563eb;">{{ number_format($totalDebits, 2) }}</td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;color:#ea580c;">{{ number_format($totalCredits, 2) }}</td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;font-weight:700;
                       color:{{ $lastRow['balance_neg'] ? '#ef4444' : '#111827' }};" class="{{ $lastRow['balance_neg'] ? '' : 'ar-text-main' }}">
                {{ $lastRow['balance_neg'] ? '−' : '' }}{{ $lastRow['balance'] }}
            </td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>

{{-- Keyboard hints + legend --}}
<div style="margin-top:10px;display:flex;gap:14px;font-size:11px;flex-wrap:wrap;align-items:center;">
    <span class="ar-text-sub">
        <span class="ar-kbd">↵ Enter</span> save &nbsp;
        <span class="ar-kbd">Tab</span> accept suggestion &nbsp;
        <span class="ar-kbd">↑ ↓</span> browse
    </span>
    <span class="ar-text-sub" style="margin-left:auto;">
        <strong style="color:#10b981;">✓</strong> reconciled &nbsp;
        <strong style="color:#60a5fa;">c</strong> cleared &nbsp;
        <strong class="ar-text-muted">n</strong> unreconciled &nbsp;|&nbsp;
        hover a row for more actions
    </span>
</div>

@endif {{-- end if accountId --}}

</div>
</x-filament-panels::page>


