<x-filament-panels::page>

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
        openSuggestions()  { this.suggestionsOpen = this.$wire.suggestions.length > 0; },
        closeSuggestions() { this.suggestionsOpen = false; this.focusedSuggestion = -1; },
        arrowDown() { if (!this.suggestionsOpen) return; this.focusedSuggestion = Math.min(this.focusedSuggestion + 1, this.$wire.suggestions.length - 1); },
        arrowUp()   { if (!this.suggestionsOpen) return; this.focusedSuggestion = Math.max(this.focusedSuggestion - 1, 0); },
        acceptFocused() { if (this.focusedSuggestion >= 0 && this.suggestionsOpen) { this.$wire.applySuggestion(this.focusedSuggestion); this.closeSuggestions(); } },
        acceptFirst()   { if (this.suggestionsOpen && this.$wire.suggestions.length > 0) { this.$wire.applySuggestion(0); this.closeSuggestions(); } }
    }"
    @click.outside="closeSuggestions()"
>

@if(!$this->accountId)

{{-- ══════════════════════════════════════════════
     ACCOUNTS LIST (landing view)
══════════════════════════════════════════════ --}}
@php
    $summaries   = $this->getAccountSummaries();
    $totalAccts  = collect($summaries)->flatten(1)->count();
@endphp

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
    <span style="font-size:13px;color:#6b7280;">
        {{ $totalAccts }} {{ Str::plural('account', $totalAccts) }} — click any row to open its register
    </span>
</div>

@forelse($summaries as $typeName => $typeAccounts)
@php
    $cfg   = $typeConfig[$typeName] ?? ['color' => '#6b7280', 'light' => 'rgba(107,114,128,.1)', 'icon' => '📋', 'label' => $typeName];
    $color = $cfg['color'];
    $light = $cfg['light'];
    $icon  = $cfg['icon'];
    $label = $cfg['label'];

    // compute per-type total (signed: neg balances subtract)
    $typeTotal = collect($typeAccounts)->sum(fn($r) =>
        $r['balance_neg']
            ? -(float) str_replace(',', '', $r['balance'])
            :  (float) str_replace(',', '', $r['balance'])
    );
    $typeTotalNeg = $typeTotal < 0;
    $typeTotalFmt = number_format(abs($typeTotal), 2);
@endphp

<div style="margin-bottom:24px;">

    {{-- Section header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                background:{{ $light }};border-left:4px solid {{ $color }};
                border-radius:8px 8px 0 0;padding:10px 16px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;line-height:1;">{{ $icon }}</span>
            <span style="font-weight:700;font-size:14px;color:{{ $color }};letter-spacing:.04em;text-transform:uppercase;">
                {{ $label }}
            </span>
            <span style="font-size:11px;background:{{ $color }};color:#fff;border-radius:999px;padding:1px 8px;font-weight:600;">
                {{ count($typeAccounts) }}
            </span>
        </div>
        <span style="font-size:13px;font-family:monospace;font-weight:700;
                     color:{{ $typeTotalNeg ? '#ef4444' : $color }};">
            {{ $typeTotalNeg ? '−' : '' }}{{ $typeTotalFmt }}
        </span>
    </div>

    {{-- Accounts table --}}
    <div style="border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tbody>
            @foreach($typeAccounts as $idx => $row)
            <tr
                wire:key="acct-{{ $row['id'] }}"
                wire:click="$set('accountId', '{{ $row['id'] }}')"
                style="border-bottom:1px solid #f3f4f6;cursor:pointer;transition:background .15s;
                       background:{{ $idx % 2 === 0 ? '#fff' : '#fafafa' }};"
                onmouseover="this.style.background='{{ $light }}'"
                onmouseout="this.style.background='{{ $idx % 2 === 0 ? '#fff' : '#fafafa' }}'"
            >
                {{-- Code badge --}}
                <td style="padding:10px 8px 10px 16px;width:1%;white-space:nowrap;">
                    @if($row['code'])
                        <span style="font-size:11px;font-family:monospace;background:#f3f4f6;color:#6b7280;
                                     border-radius:4px;padding:2px 6px;border:1px solid #e5e7eb;">
                            {{ $row['code'] }}
                        </span>
                    @endif
                </td>

                {{-- Name --}}
                <td style="padding:10px 8px;">
                    <span style="font-weight:600;color:#111827;">{{ $row['name'] }}</span>
                </td>

                {{-- Tx count chip --}}
                <td style="padding:10px 8px;white-space:nowrap;text-align:right;">
                    @if($row['tx_count'] > 0)
                        <span style="font-size:11px;background:#f3f4f6;color:#6b7280;
                                     border-radius:999px;padding:2px 8px;border:1px solid #e5e7eb;">
                            {{ $row['tx_count'] }} txn
                        </span>
                    @else
                        <span style="font-size:11px;color:#d1d5db;">no transactions</span>
                    @endif
                </td>

                {{-- Balance --}}
                <td style="padding:10px 16px 10px 8px;text-align:right;white-space:nowrap;
                           font-family:monospace;font-weight:700;font-size:13px;
                           color:{{ $row['balance_neg'] ? '#ef4444' : $color }};">
                    {{ $row['balance_neg'] ? '−' : '' }}{{ $row['balance'] }}
                </td>

                {{-- Chevron --}}
                <td style="padding:10px 12px 10px 0;text-align:right;width:1%;">
                    <svg style="width:14px;height:14px;color:#d1d5db;display:inline;" fill="none" stroke="{{ $color }}" viewBox="0 0 24 24" opacity=".5">
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
<div style="text-align:center;padding:60px 20px;color:#9ca3af;">
    <div style="font-size:40px;margin-bottom:12px;">📂</div>
    <div style="font-size:16px;font-weight:600;margin-bottom:4px;">No accounts found</div>
    <div style="font-size:13px;">Go to the Accounts section to set up your chart of accounts.</div>
</div>
@endforelse

@else

{{-- ══════════════════════════════════════════════
     REGISTER HEADER (back button + account card)
══════════════════════════════════════════════ --}}
@php
    $acct     = $this->getCurrentAccount();
    $typeName = strtoupper($acct?->accountType?->name ?? 'ASSET');
    $cfg      = $typeConfig[$typeName] ?? ['color' => '#6b7280', 'light' => 'rgba(107,114,128,.1)', 'icon' => '📋', 'label' => $typeName];
    $hColor   = $cfg['color'];
    $hLight   = $cfg['light'];
    $hIcon    = $cfg['icon'];
    $hLabel   = $cfg['label'];
    $stats    = $this->getCurrentAccountStats();
@endphp

{{-- Back button --}}
<div style="margin-bottom:14px;">
    <button
        wire:click="$set('accountId', '')"
        style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;
               background:none;border:1px solid #e5e7eb;border-radius:6px;padding:5px 12px;
               cursor:pointer;transition:all .15s;"
        onmouseover="this.style.color='#10b981';this.style.borderColor='#10b981';"
        onmouseout="this.style.color='#6b7280';this.style.borderColor='#e5e7eb';"
    >
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        All Accounts
    </button>
</div>

{{-- Account header card --}}
<div style="border-left:5px solid {{ $hColor }};background:{{ $hLight }};border-radius:10px;
            padding:18px 22px;margin-bottom:18px;display:flex;align-items:center;
            justify-content:space-between;flex-wrap:wrap;gap:14px;">

    {{-- Left: icon + name --}}
    <div style="display:flex;align-items:center;gap:14px;">
        <span style="font-size:32px;line-height:1;">{{ $hIcon }}</span>
        <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if($acct?->code)
                    <span style="font-size:11px;font-family:monospace;background:#fff;color:#6b7280;
                                 border:1px solid #d1d5db;border-radius:4px;padding:2px 7px;">
                        {{ $acct->code }}
                    </span>
                @endif
                <span style="font-size:18px;font-weight:800;color:#111827;">{{ $acct?->name }}</span>
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
                             color:{{ $hColor }};background:{{ $hLight }};border:1px solid {{ $hColor }};
                             border-radius:999px;padding:2px 9px;">
                    {{ $hLabel }}
                </span>
                @if($acct?->currency)
                    <span style="font-size:12px;color:#9ca3af;">{{ $acct->currency->code }}</span>
                @endif
            </div>
            @if($acct?->description)
                <div style="font-size:12px;color:#6b7280;margin-top:4px;">{{ $acct->description }}</div>
            @endif
        </div>
    </div>

    {{-- Right: balance + stats --}}
    <div style="text-align:right;">
        <div style="font-size:26px;font-family:monospace;font-weight:800;
                    color:{{ $stats['balance_neg'] ? '#ef4444' : $hColor }};">
            {{ $stats['balance_neg'] ? '−' : '' }}{{ $stats['balance'] }}
        </div>
        <div style="display:flex;gap:18px;margin-top:6px;font-size:11px;color:#6b7280;">
            <span>
                <strong style="color:#374151;">{{ $stats['tx_count'] }}</strong> txn
            </span>
            <span>
                Dr: <strong style="color:#3b82f6;">{{ $stats['total_debit'] }}</strong>
            </span>
            <span>
                Cr: <strong style="color:#f97316;">{{ $stats['total_credit'] }}</strong>
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     REGISTER TABLE
══════════════════════════════════════════════ --}}
<div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
<table style="width:100%;border-collapse:collapse;font-size:13px;">
    <thead>
        <tr style="background:#f9fafb;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.06em;text-align:left;">
            <th style="padding:8px;width:30px;text-align:center;">R</th>
            <th style="padding:8px;width:100px;">Date</th>
            <th style="padding:8px;width:80px;">Num</th>
            <th style="padding:8px;">Description / Memo</th>
            <th style="padding:8px;">Transfer Account</th>
            <th style="padding:8px;text-align:right;width:110px;color:#3b82f6;">Debit</th>
            <th style="padding:8px;text-align:right;width:110px;color:#f97316;">Credit</th>
            <th style="padding:8px;text-align:right;width:120px;">Balance</th>
            <th style="padding:8px;width:50px;"></th>
        </tr>
    </thead>

    {{-- ── QUICK ENTRY ROW ── --}}
    <tbody>
    <tr style="background:#f0fdf4;border-bottom:2px solid #10b981;">
        {{-- Reconcile placeholder --}}
        <td style="padding:6px 8px;text-align:center;color:#10b981;font-weight:700;">+</td>

        {{-- Date --}}
        <td style="padding:4px;">
            <input
                type="date"
                wire:model="entryDate"
                style="width:100%;border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 6px;font-size:12px;outline:none;"
                onfocus="this.style.borderColor='#10b981'"
                onblur="this.style.borderColor='#d1d5db'"
            />
        </td>

        {{-- Num --}}
        <td style="padding:4px;">
            <input
                type="text"
                wire:model="entryNum"
                placeholder="Ref#"
                style="width:100%;border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 6px;font-size:12px;outline:none;"
                onfocus="this.style.borderColor='#10b981'"
                onblur="this.style.borderColor='#d1d5db'"
            />
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
                style="width:100%;border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 6px;font-size:12px;outline:none;"
                onfocus="this.style.borderColor='#10b981'"
                onblur="this.style.borderColor='#d1d5db'"
            />

            {{-- Suggestions dropdown --}}
            <div
                x-show="suggestionsOpen && $wire.suggestions.length > 0"
                x-transition
                style="position:absolute;z-index:50;left:0;top:100%;margin-top:2px;width:100%;min-width:280px;
                       background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow:hidden;"
            >
                <div style="font-size:11px;color:#9ca3af;padding:6px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                    Suggestions from history — Tab or ↵ to fill
                </div>
                <template x-for="(s, i) in $wire.suggestions" :key="i">
                    <div
                        @click="$wire.applySuggestion(i); closeSuggestions()"
                        @mouseenter="focusedSuggestion = i"
                        :style="focusedSuggestion === i ? 'background:#f0fdf4;cursor:pointer;' : 'cursor:pointer;'"
                        style="padding:8px 12px;border-bottom:1px solid #f3f4f6;"
                    >
                        <div style="font-weight:600;font-size:12px;color:#111827;" x-text="s.description"></div>
                        <div style="font-size:11px;color:#9ca3af;margin-top:2px;display:flex;gap:10px;">
                            <span>→ <span x-text="s.transfer_account"></span></span>
                            <span x-show="s.debit" style="color:#3b82f6;">Dr: <span x-text="s.debit"></span></span>
                            <span x-show="s.credit" style="color:#f97316;">Cr: <span x-text="s.credit"></span></span>
                        </div>
                    </div>
                </template>
            </div>
        </td>

        {{-- Transfer Account --}}
        <td style="padding:4px;">
            <select
                wire:model="entryTransferAccountId"
                style="width:100%;border:1px solid #d1d5db;border-radius:5px;background:#fff;color:#111827;padding:4px 6px;font-size:12px;"
            >
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

        {{-- Debit --}}
        <td style="padding:4px;">
            <input
                type="number"
                wire:model="entryDebit"
                placeholder="0.00"
                step="0.01"
                min="0"
                @input="if ($event.target.value) { $wire.set('entryCredit', '') }"
                style="width:100%;border:1px solid #bfdbfe;border-radius:5px;background:#eff6ff;color:#1d4ed8;
                       padding:4px 6px;font-size:12px;font-family:monospace;text-align:right;outline:none;"
                onfocus="this.style.borderColor='#3b82f6'"
                onblur="this.style.borderColor='#bfdbfe'"
            />
        </td>

        {{-- Credit --}}
        <td style="padding:4px;">
            <input
                type="number"
                wire:model="entryCredit"
                placeholder="0.00"
                step="0.01"
                min="0"
                @input="if ($event.target.value) { $wire.set('entryDebit', '') }"
                style="width:100%;border:1px solid #fed7aa;border-radius:5px;background:#fff7ed;color:#c2410c;
                       padding:4px 6px;font-size:12px;font-family:monospace;text-align:right;outline:none;"
                onfocus="this.style.borderColor='#f97316'"
                onblur="this.style.borderColor='#fed7aa'"
            />
        </td>

        {{-- Balance placeholder --}}
        <td style="padding:6px 10px;text-align:right;font-size:12px;color:#d1d5db;font-family:monospace;">—</td>

        {{-- Save button --}}
        <td style="padding:4px 8px;text-align:center;">
            <button
                wire:click="saveEntry"
                wire:loading.attr="disabled"
                style="background:#059669;color:#fff;font-size:12px;font-weight:600;padding:5px 10px;
                       border-radius:5px;border:none;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='#047857'"
                onmouseout="this.style.background='#059669'"
                title="Save (Enter)"
            >
                <span wire:loading.remove wire:target="saveEntry">Save</span>
                <span wire:loading wire:target="saveEntry">…</span>
            </button>
        </td>
    </tr>

    {{-- ── REGISTER ROWS ── --}}
    @php $rows = $this->getRegisterRows(); @endphp
    @forelse($rows as $rowIdx => $row)
        <tr
            wire:key="row-{{ $row['split_id'] }}"
            style="border-bottom:1px solid #f3f4f6;transition:background .12s;
                   background:{{ $rowIdx % 2 === 0 ? '#fff' : '#fafafa' }};
                   {{ $row['is_posted'] ? 'opacity:.75;' : '' }}"
            onmouseover="this.style.background='#f0fdf4'"
            onmouseout="this.style.background='{{ $rowIdx % 2 === 0 ? '#fff' : '#fafafa' }}'"
        >
            {{-- Reconcile indicator --}}
            <td style="padding:10px 8px;text-align:center;font-size:12px;">
                @if($row['reconciled'] === 'y' || $row['reconciled'] === 'f')
                    <span style="color:#10b981;" title="Reconciled">✓</span>
                @elseif($row['reconciled'] === 'c')
                    <span style="color:#60a5fa;" title="Cleared">c</span>
                @else
                    <span style="color:#d1d5db;" title="Not reconciled">n</span>
                @endif
            </td>

            {{-- Date --}}
            <td style="padding:10px 8px;font-size:12px;font-family:monospace;color:#374151;white-space:nowrap;">
                {{ $row['date'] }}
            </td>

            {{-- Num --}}
            <td style="padding:10px 8px;font-size:12px;font-family:monospace;color:#9ca3af;">
                {{ $row['num'] }}
            </td>

            {{-- Description + memo --}}
            <td style="padding:10px 8px;max-width:240px;">
                <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $row['description'] }}
                    @if($row['is_posted'])
                        <span style="margin-left:6px;font-size:10px;font-weight:700;letter-spacing:.04em;
                                     color:#059669;background:#d1fae5;border-radius:999px;padding:1px 7px;">
                            POSTED
                        </span>
                    @endif
                </div>
                @if($row['memo'])
                    <div style="font-size:11px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;">
                        {{ $row['memo'] }}
                    </div>
                @endif
            </td>

            {{-- Transfer --}}
            <td style="padding:10px 8px;font-size:12px;color:#6b7280;max-width:180px;
                       white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                       {{ $row['is_split'] ? 'font-style:italic;' : '' }}">
                {{ $row['transfer'] }}
            </td>

            {{-- Debit --}}
            <td style="padding:10px 8px;text-align:right;font-size:12px;font-family:monospace;
                       {{ $row['debit'] ? 'font-weight:700;color:#2563eb;' : 'color:#d1d5db;' }}">
                {{ $row['debit'] ?: '—' }}
            </td>

            {{-- Credit --}}
            <td style="padding:10px 8px;text-align:right;font-size:12px;font-family:monospace;
                       {{ $row['credit'] ? 'font-weight:700;color:#ea580c;' : 'color:#d1d5db;' }}">
                {{ $row['credit'] ?: '—' }}
            </td>

            {{-- Running balance --}}
            <td style="padding:10px 8px;text-align:right;font-size:13px;font-family:monospace;font-weight:700;
                       color:{{ $row['balance_neg'] ? '#ef4444' : '#111827' }};">
                {{ $row['balance_neg'] ? '−' : '' }}{{ $row['balance'] }}
            </td>

            {{-- Edit link --}}
            <td style="padding:10px 8px;text-align:center;">
                <a
                    href="{{ route('filament.admin.resources.transactions.edit', $row['transaction_id']) }}"
                    style="color:#d1d5db;transition:color .15s;"
                    onmouseover="this.style.color='#10b981'"
                    onmouseout="this.style.color='#d1d5db'"
                    title="Edit full transaction"
                    wire:navigate
                >
                    <svg style="width:14px;height:14px;display:inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" style="text-align:center;padding:48px 20px;color:#9ca3af;">
                <div style="font-size:36px;margin-bottom:10px;">📂</div>
                <div style="font-size:15px;font-weight:600;margin-bottom:4px;">No transactions yet</div>
                <div style="font-size:12px;">Use the entry row above to record the first transaction.</div>
            </td>
        </tr>
    @endforelse
    </tbody>

    {{-- Footer tally --}}
    @if(count($rows) > 0)
    @php
        $totalDebits  = collect($rows)->sum(fn($r) => (float) str_replace(',', '', $r['debit']));
        $totalCredits = collect($rows)->sum(fn($r) => (float) str_replace(',', '', $r['credit']));
        $lastRow      = $rows[0]; // rows are reversed (first = most recent)
    @endphp
    <tfoot>
        <tr style="background:#f9fafb;font-size:12px;font-weight:700;border-top:2px solid #e5e7eb;">
            <td colspan="5" style="padding:10px 12px;color:#6b7280;">
                {{ count($rows) }} {{ Str::plural('transaction', count($rows)) }}
            </td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;color:#2563eb;">
                {{ number_format($totalDebits, 2) }}
            </td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;color:#ea580c;">
                {{ number_format($totalCredits, 2) }}
            </td>
            <td style="padding:10px 8px;text-align:right;font-family:monospace;
                       color:{{ $lastRow['balance_neg'] ? '#ef4444' : '#111827' }};">
                {{ $lastRow['balance_neg'] ? '−' : '' }}{{ $lastRow['balance'] }}
            </td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>

{{-- Keyboard hints --}}
<div style="margin-top:10px;display:flex;gap:20px;font-size:11px;color:#9ca3af;flex-wrap:wrap;">
    <span>
        <kbd style="font-family:monospace;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;
                    padding:1px 5px;font-size:11px;">↵ Enter</kbd>
        to save
    </span>
    <span>
        <kbd style="font-family:monospace;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;
                    padding:1px 5px;font-size:11px;">Tab</kbd>
        accept suggestion
    </span>
    <span>
        <kbd style="font-family:monospace;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;
                    padding:1px 5px;font-size:11px;">↑ ↓</kbd>
        browse suggestions
    </span>
    <span style="margin-left:auto;">
        <strong style="color:#10b981;">✓</strong> reconciled &nbsp;
        <strong style="color:#60a5fa;">c</strong> cleared &nbsp;
        <strong style="color:#d1d5db;">n</strong> not reconciled
    </span>
</div>

@endif {{-- end if accountId --}}

</div>
</x-filament-panels::page>

