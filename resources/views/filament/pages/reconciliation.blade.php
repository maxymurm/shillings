<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Reconciliation Settings Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Reconciliation Settings
            </x-slot>
            
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Balance Summary --}}
        @if($this->account_id)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Opening Balance</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($this->opening_balance ?? 0, 2) }}
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Cleared Balance</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($clearedBalance, 2) }}
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Statement Balance</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($this->statement_balance ?? 0, 2) }}
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Difference</div>
                    <div class="text-2xl font-bold {{ $isBalanced ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $isBalanced ? '✓ Balanced' : number_format($difference, 2) }}
                    </div>
                </div>
            </x-filament::section>
        </div>
        @endif

        {{-- Transactions Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Transactions to Reconcile
            </x-slot>
            
            {{ $this->table }}
        </x-filament::section>

        {{-- Complete Reconciliation Button --}}
        @if($this->account_id && $isBalanced)
        <div class="flex justify-end">
            <x-filament::button
                wire:click="completeReconciliation"
                color="success"
                size="lg"
                icon="heroicon-o-check-circle"
            >
                Complete Reconciliation
            </x-filament::button>
        </div>
        @endif
    </div>
</x-filament-panels::page>
