<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Report Parameters Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Report Parameters
            </x-slot>
            
            <form wire:submit.prevent="generateReport">
                {{ $this->form }}
                
                <div class="flex gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">
                        Generate Report
                    </x-filament::button>
                    
                    @if($reportData)
                    <x-filament::button wire:click="exportPdf" color="gray">
                        Export PDF
                    </x-filament::button>
                    
                    <x-filament::button wire:click="exportExcel" color="gray">
                        Export Excel
                    </x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>

        {{-- Report Display --}}
        @if($reportData)
        <x-filament::section>
            <x-slot name="heading">
                {{ $reportTitle }}
            </x-slot>

            @if($reportType === 'trial_balance')
                @include('filament.pages.reports.trial-balance', ['data' => $reportData])
            @elseif($reportType === 'balance_sheet')
                @include('filament.pages.reports.balance-sheet', ['data' => $reportData])
            @elseif($reportType === 'income_statement')
                @include('filament.pages.reports.income-statement', ['data' => $reportData])
            @elseif($reportType === 'cash_flow')
                @include('filament.pages.reports.cash-flow', ['data' => $reportData])
            @elseif($reportType === 'general_ledger')
                @include('filament.pages.reports.general-ledger', ['data' => $reportData])
            @endif
        </x-filament::section>
        @else
        <x-filament::section>
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-document-chart-bar class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                <p class="text-lg font-medium">Select a report type and parameters</p>
                <p class="text-sm">Click "Generate Report" to view your financial data</p>
            </div>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
