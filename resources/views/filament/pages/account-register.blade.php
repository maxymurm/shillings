<x-filament-panels::page>
<div
    x-data="{
        suggestionsOpen: false,
        focusedSuggestion: -1,

        openSuggestions() { this.suggestionsOpen = this.$wire.suggestions.length > 0; },
        closeSuggestions() { this.suggestionsOpen = false; this.focusedSuggestion = -1; },

        arrowDown() {
            if (!this.suggestionsOpen) return;
            this.focusedSuggestion = Math.min(this.focusedSuggestion + 1, this.$wire.suggestions.length - 1);
        },
        arrowUp() {
            if (!this.suggestionsOpen) return;
            this.focusedSuggestion = Math.max(this.focusedSuggestion - 1, 0);
        },
        acceptFocused() {
            if (this.focusedSuggestion >= 0 && this.suggestionsOpen) {
                this.$wire.applySuggestion(this.focusedSuggestion);
                this.closeSuggestions();
            }
        },
        acceptFirst() {
            if (this.suggestionsOpen && this.$wire.suggestions.length > 0) {
                this.$wire.applySuggestion(0);
                this.closeSuggestions();
            }
        }
    }"
    @click.outside="closeSuggestions()"
>

@if(!$this->accountId)

{{-- ══════════════════════════════════════════════
     ACCOUNTS LIST (landing view)
══════════════════════════════════════════════ --}}
<p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Click on any account to open its transaction register.</p>

@php $summaries = $this->getAccountSummaries(); @endphp

@forelse($summaries as $typeName => $typeAccounts)
<div class="mb-5">
    <div class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400 px-1 pb-1 mb-1 border-b border-gray-200 dark:border-gray-700">
        {{ $typeName }}
    </div>
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <tbody>
            @foreach($typeAccounts as $row)
            <tr
                wire:key="acct-{{ $row['id'] }}"
                wire:click="$set('accountId', '{{ $row['id'] }}')"
                class="border-b border-gray-100 dark:border-gray-800 last:border-0 cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group"
            >
                <td class="px-3 py-2.5">
                    <div class="flex items-center gap-2">
                        @if($row['code'])
                            <span class="text-xs font-mono text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ $row['code'] }}</span>
                        @endif
                        <span class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                            {{ $row['name'] }}
                        </span>
                    </div>
                </td>
                <td class="px-3 py-2.5 text-xs text-gray-400 dark:text-gray-500 text-right whitespace-nowrap">
                    {{ $row['tx_count'] }} {{ Str::plural('transaction', $row['tx_count']) }}
                </td>
                <td class="px-3 py-2.5 text-right font-mono text-sm font-semibold whitespace-nowrap
                    {{ $row['balance_neg'] ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $row['balance_neg'] ? '-' : '' }}{{ $row['balance'] }}
                </td>
                <td class="px-3 py-2.5 text-right">
                    <svg class="inline w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="flex flex-col items-center justify-center py-20 text-gray-400">
    <x-filament::icon icon="heroicon-o-table-cells" class="w-12 h-12 mb-3 opacity-30" />
    <p class="text-lg font-medium">No accounts found</p>
    <p class="text-sm mt-1">Add accounts in the Accounts section first.</p>
</div>
@endforelse

@else

{{-- ══════════════════════════════════════════════
     REGISTER HEADER (back button + account info)
══════════════════════════════════════════════ --}}
@php $acct = $this->getCurrentAccount(); @endphp
<div class="mb-4 flex flex-wrap items-center gap-3">
    <button
        wire:click="$set('accountId', '')"
        class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        All Accounts
    </button>
    <span class="text-gray-300 dark:text-gray-600">|</span>
    <div>
        <span class="font-semibold text-gray-900 dark:text-white">
            {{ $acct?->code ? "[{$acct->code}] " : "" }}{{ $acct?->name }}
        </span>
        @if($acct?->accountType)
            <span class="ml-2 text-xs text-gray-400 dark:text-gray-500">{{ $acct->accountType->name }}</span>
        @endif
        @if($acct?->currency)
            <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">&middot; {{ $acct->currency->code }}</span>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════
     REGISTER TABLE
══════════════════════════════════════════════ --}}
<div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
<table class="w-full text-sm">
    <thead>
        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-left text-xs uppercase tracking-wider">
            <th class="px-2 py-2 w-7">R</th>
            <th class="px-2 py-2 w-28">Date</th>
            <th class="px-2 py-2 w-20">Num</th>
            <th class="px-2 py-2">Description / Memo</th>
            <th class="px-2 py-2">Transfer Account</th>
            <th class="px-2 py-2 text-right w-28">Debit</th>
            <th class="px-2 py-2 text-right w-28">Credit</th>
            <th class="px-2 py-2 text-right w-32">Balance</th>
            <th class="px-2 py-2 w-14"></th>
        </tr>
    </thead>

    {{-- ── QUICK ENTRY ROW ── --}}
    <tbody>
    <tr class="bg-emerald-50 dark:bg-emerald-900/20 border-b-2 border-emerald-400 dark:border-emerald-600">
        {{-- Reconcile placeholder --}}
        <td class="px-2 py-1 text-center text-emerald-500">+</td>

        {{-- Date --}}
        <td class="px-1 py-1">
            <input
                type="date"
                wire:model="entryDate"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400"
            />
        </td>

        {{-- Num --}}
        <td class="px-1 py-1">
            <input
                type="text"
                wire:model="entryNum"
                placeholder="Ref#"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400"
            />
        </td>

        {{-- Description with autocomplete --}}
        <td class="px-1 py-1 relative">
            <input
                type="text"
                wire:model.live.debounce.400ms="entryDescription"
                placeholder="Description (type to get suggestions)"
                autocomplete="off"
                @focus="openSuggestions()"
                @input="$wire.suggestions.length > 0 ? suggestionsOpen = true : suggestionsOpen = false"
                @keydown.arrow-down.prevent="arrowDown()"
                @keydown.arrow-up.prevent="arrowUp()"
                @keydown.enter.prevent="if (suggestionsOpen) { acceptFocused() } else { $wire.saveEntry() }"
                @keydown.tab="acceptFirst(); $nextTick(() => suggestionsOpen = false)"
                @keydown.escape="closeSuggestions()"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400"
            />

            {{-- Suggestions dropdown --}}
            <div
                x-show="suggestionsOpen && $wire.suggestions.length > 0"
                x-transition
                class="absolute z-50 left-0 top-full mt-0.5 w-full min-w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-xl overflow-hidden"
            >
                <div class="text-xs text-gray-400 px-3 py-1 bg-gray-50 dark:bg-gray-900 border-b dark:border-gray-700">
                    Suggestions from history — Tab or ↵ to fill
                </div>
                <template x-for="(s, i) in $wire.suggestions" :key="i">
                    <div
                        @click="$wire.applySuggestion(i); closeSuggestions()"
                        @mouseenter="focusedSuggestion = i"
                        :class="focusedSuggestion === i
                            ? 'bg-emerald-50 dark:bg-emerald-900/30 cursor-pointer'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer'"
                        class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                    >
                        <div class="font-medium text-gray-900 dark:text-white text-xs" x-text="s.description"></div>
                        <div class="text-gray-400 text-xs mt-0.5 flex gap-3">
                            <span>→ <span x-text="s.transfer_account"></span></span>
                            <span x-show="s.debit" class="text-blue-500">Dr: <span x-text="s.debit"></span></span>
                            <span x-show="s.credit" class="text-orange-500">Cr: <span x-text="s.credit"></span></span>
                        </div>
                    </div>
                </template>
            </div>
        </td>

        {{-- Transfer Account --}}
        <td class="px-1 py-1">
            <select
                wire:model="entryTransferAccountId"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400"
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
        <td class="px-1 py-1">
            <input
                type="number"
                wire:model="entryDebit"
                placeholder="0.00"
                step="0.01"
                min="0"
                @input="if ($event.target.value) { $wire.set('entryCredit', '') }"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs text-right font-mono focus:ring-1 focus:ring-blue-400"
            />
        </td>

        {{-- Credit --}}
        <td class="px-1 py-1">
            <input
                type="number"
                wire:model="entryCredit"
                placeholder="0.00"
                step="0.01"
                min="0"
                @input="if ($event.target.value) { $wire.set('entryDebit', '') }"
                class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-xs text-right font-mono focus:ring-1 focus:ring-orange-400"
            />
        </td>

        {{-- Balance (shows current account balance) --}}
        <td class="px-2 py-1 text-right text-xs text-gray-400 font-mono">—</td>

        {{-- Save button --}}
        <td class="px-2 py-1 text-center">
            <button
                wire:click="saveEntry"
                wire:loading.attr="disabled"
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-medium px-2 py-1 rounded"
                title="Save (Enter)"
            >
                <span wire:loading.remove wire:target="saveEntry">Save</span>
                <span wire:loading wire:target="saveEntry">…</span>
            </button>
        </td>
    </tr>

    {{-- ── REGISTER ROWS ── --}}
    @php $rows = $this->getRegisterRows(); @endphp
    @forelse($rows as $row)
        <tr
            wire:key="row-{{ $row['split_id'] }}"
            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors {{ $row['is_posted'] ? 'opacity-80' : '' }}"
        >
            {{-- Reconcile indicator --}}
            <td class="px-2 py-1.5 text-center text-xs">
                @if($row['reconciled'] === 'y' || $row['reconciled'] === 'f')
                    <span class="text-emerald-500" title="Reconciled">✓</span>
                @elseif($row['reconciled'] === 'c')
                    <span class="text-blue-400" title="Cleared">c</span>
                @else
                    <span class="text-gray-300 dark:text-gray-600">n</span>
                @endif
            </td>

            {{-- Date --}}
            <td class="px-2 py-1.5 text-xs text-gray-600 dark:text-gray-300 font-mono whitespace-nowrap">
                {{ $row['date'] }}
            </td>

            {{-- Num --}}
            <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 font-mono">
                {{ $row['num'] }}
            </td>

            {{-- Description + memo --}}
            <td class="px-2 py-1.5 max-w-xs">
                <div class="text-sm text-gray-900 dark:text-gray-100 font-medium truncate">
                    {{ $row['description'] }}
                    @if($row['is_posted'])
                        <span class="ml-1 text-xs text-emerald-600 dark:text-emerald-400">[posted]</span>
                    @endif
                </div>
                @if($row['memo'])
                    <div class="text-xs text-gray-400 truncate">{{ $row['memo'] }}</div>
                @endif
            </td>

            {{-- Transfer --}}
            <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                @if($row['is_split'])
                    <span class="italic">{{ $row['transfer'] }}</span>
                @else
                    {{ $row['transfer'] }}
                @endif
            </td>

            {{-- Debit --}}
            <td class="px-2 py-1.5 text-right text-xs font-mono {{ $row['debit'] ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-300 dark:text-gray-600' }}">
                {{ $row['debit'] ?: '—' }}
            </td>

            {{-- Credit --}}
            <td class="px-2 py-1.5 text-right text-xs font-mono {{ $row['credit'] ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-gray-300 dark:text-gray-600' }}">
                {{ $row['credit'] ?: '—' }}
            </td>

            {{-- Running balance --}}
            <td class="px-2 py-1.5 text-right text-xs font-mono font-semibold {{ $row['balance_neg'] ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                {{ $row['balance_neg'] ? '-' : '' }}{{ $row['balance'] }}
            </td>

            {{-- Edit link --}}
            <td class="px-2 py-1.5 text-center">
                <a
                    href="{{ route('filament.admin.resources.transactions.edit', $row['transaction_id']) }}"
                    class="text-gray-400 hover:text-emerald-500 transition-colors"
                    title="Edit full transaction"
                    wire:navigate
                >
                    <svg class="inline w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="9" class="text-center py-12 text-gray-400">
                <div class="text-2xl mb-2">📂</div>
                <div class="font-medium">No transactions yet</div>
                <div class="text-xs mt-1">Use the entry row above to add the first transaction</div>
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
        <tr class="bg-gray-100 dark:bg-gray-800 text-xs font-semibold">
            <td colspan="5" class="px-2 py-2 text-gray-500 dark:text-gray-400">
                {{ count($rows) }} transaction(s)
            </td>
            <td class="px-2 py-2 text-right font-mono text-blue-600 dark:text-blue-400">
                {{ number_format($totalDebits, 2) }}
            </td>
            <td class="px-2 py-2 text-right font-mono text-orange-600 dark:text-orange-400">
                {{ number_format($totalCredits, 2) }}
            </td>
            <td class="px-2 py-2 text-right font-mono {{ $lastRow['balance_neg'] ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                {{ $lastRow['balance_neg'] ? '-' : '' }}{{ $lastRow['balance'] }}
            </td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
</div>

<div class="mt-3 text-xs text-gray-400 flex gap-6">
    <span>⌨ <strong>↵ Enter</strong> to save · <strong>Tab</strong> to accept suggestion · <strong>↑↓</strong> to browse suggestions</span>
    <span>✓ = reconciled &nbsp; c = cleared &nbsp; n = not reconciled</span>
</div>

@endif {{-- end if accountId --}}

</div>
</x-filament-panels::page>
