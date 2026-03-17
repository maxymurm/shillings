<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Report Parameters Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Tax Summary Parameters
            </x-slot>

            <form wire:submit.prevent="generateReport">
                {{ $this->form }}

                <div class="flex gap-3 mt-4">
                    <x-filament::button type="submit" color="primary">
                        Generate Report
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Report Display --}}
        @if($reportData)
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 20px;">
                <p style="font-size: 13px; color: #065f46; margin-bottom: 4px;">Total Sales Tax Collected</p>
                <p style="font-size: 24px; font-weight: 700; color: #047857;">
                    {{ $reportData['totals']['collected_formatted'] ?? number_format($reportData['totals']['collected'] ?? 0, 2) }}
                </p>
            </div>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px;">
                <p style="font-size: 13px; color: #991b1b; margin-bottom: 4px;">Total Purchase Tax Paid</p>
                <p style="font-size: 24px; font-weight: 700; color: #dc2626;">
                    {{ $reportData['totals']['paid_formatted'] ?? number_format($reportData['totals']['paid'] ?? 0, 2) }}
                </p>
            </div>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px;">
                <p style="font-size: 13px; color: #1e40af; margin-bottom: 4px;">
                    Net Tax {{ ($reportData['totals']['net'] ?? 0) >= 0 ? 'Payable' : 'Recoverable' }}
                </p>
                <p style="font-size: 24px; font-weight: 700; color: #2563eb;">
                    {{ $reportData['totals']['net_formatted'] ?? number_format(abs($reportData['totals']['net'] ?? 0), 2) }}
                </p>
            </div>
        </div>

        {{-- Tax Detail Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Tax Breakdown — {{ $reportData['period']['start_date'] ?? '' }} to {{ $reportData['period']['end_date'] ?? '' }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-semibold">Tax Name</th>
                            <th class="text-right py-3 px-4 font-semibold">Rate</th>
                            <th class="text-right py-3 px-4 font-semibold">Collected (Sales)</th>
                            <th class="text-right py-3 px-4 font-semibold">Paid (Purchases)</th>
                            <th class="text-right py-3 px-4 font-semibold">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData['taxes'] ?? [] as $tax)
                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td class="py-2 px-4">{{ $tax['tax_name'] }}</td>
                            <td class="py-2 px-4 text-right font-mono">{{ number_format($tax['tax_rate'], 2) }}%</td>
                            <td class="py-2 px-4 text-right font-mono" style="color: #047857;">
                                {{ $tax['collected_formatted'] ?? number_format($tax['collected'], 2) }}
                            </td>
                            <td class="py-2 px-4 text-right font-mono" style="color: #dc2626;">
                                {{ $tax['paid_formatted'] ?? number_format($tax['paid'], 2) }}
                            </td>
                            <td class="py-2 px-4 text-right font-mono font-semibold">
                                {{ $tax['net_formatted'] ?? number_format($tax['net'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center text-gray-500 dark:text-gray-400">
                                No taxes found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($reportData['taxes'] ?? []) > 0)
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                            <td class="py-3 px-4">Total ({{ $reportData['tax_count'] ?? 0 }} taxes)</td>
                            <td class="py-3 px-4"></td>
                            <td class="py-3 px-4 text-right font-mono" style="color: #047857;">
                                {{ $reportData['totals']['collected_formatted'] ?? number_format($reportData['totals']['collected'] ?? 0, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono" style="color: #dc2626;">
                                {{ $reportData['totals']['paid_formatted'] ?? number_format($reportData['totals']['paid'] ?? 0, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono">
                                {{ $reportData['totals']['net_formatted'] ?? number_format(abs($reportData['totals']['net'] ?? 0), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
        @else
        <x-filament::section>
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-receipt-percent class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                <p class="text-lg font-medium">Select a date range</p>
                <p class="text-sm">Click "Generate Report" to view your tax summary</p>
            </div>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
