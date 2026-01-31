<div class="space-y-8">
    @foreach($data['accounts'] ?? [] as $account)
    <div>
        <h3 class="text-lg font-semibold mb-2">
            @if($account['code'])
                <span class="text-gray-500">[{{ $account['code'] }}]</span>
            @endif
            {{ $account['name'] }}
        </h3>
        
        <div class="text-sm text-gray-500 mb-3">
            Opening Balance: {{ number_format($account['opening_balance'] ?? 0, 2) }}
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 font-semibold">Date</th>
                        <th class="text-left py-2 px-3 font-semibold">Description</th>
                        <th class="text-left py-2 px-3 font-semibold">Ref</th>
                        <th class="text-right py-2 px-3 font-semibold">Debit</th>
                        <th class="text-right py-2 px-3 font-semibold">Credit</th>
                        <th class="text-right py-2 px-3 font-semibold">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($account['entries'] ?? [] as $entry)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="py-2 px-3">{{ \Carbon\Carbon::parse($entry['date'])->format('M d, Y') }}</td>
                        <td class="py-2 px-3">{{ $entry['description'] }}</td>
                        <td class="py-2 px-3 text-gray-500">{{ $entry['reference'] ?? '' }}</td>
                        <td class="py-2 px-3 text-right font-mono">
                            @if(($entry['debit'] ?? 0) > 0)
                                {{ number_format($entry['debit'], 2) }}
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right font-mono">
                            @if(($entry['credit'] ?? 0) > 0)
                                {{ number_format($entry['credit'], 2) }}
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-semibold">
                            {{ number_format($entry['running_balance'] ?? 0, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                        <td class="py-3 px-3" colspan="3">Closing Balance</td>
                        <td class="py-3 px-3 text-right font-mono">{{ number_format($account['total_debits'] ?? 0, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono">{{ number_format($account['total_credits'] ?? 0, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono">{{ number_format($account['closing_balance'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach
    
    @if(empty($data['accounts']))
    <div class="text-center py-8 text-gray-500">
        No transactions found for the selected criteria.
    </div>
    @endif
</div>
