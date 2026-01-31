<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="text-left py-3 px-4 font-semibold">Account</th>
                <th class="text-right py-3 px-4 font-semibold">Debit</th>
                <th class="text-right py-3 px-4 font-semibold">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['accounts'] ?? [] as $account)
            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                <td class="py-2 px-4">
                    @if($account['code'])
                        <span class="text-gray-500">[{{ $account['code'] }}]</span>
                    @endif
                    {{ $account['name'] }}
                </td>
                <td class="py-2 px-4 text-right font-mono">
                    @if($account['debit'] > 0)
                        {{ number_format($account['debit'], 2) }}
                    @endif
                </td>
                <td class="py-2 px-4 text-right font-mono">
                    @if($account['credit'] > 0)
                        {{ number_format($account['credit'], 2) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                <td class="py-3 px-4">Total</td>
                <td class="py-3 px-4 text-right font-mono">{{ number_format($data['total_debits'] ?? 0, 2) }}</td>
                <td class="py-3 px-4 text-right font-mono">{{ number_format($data['total_credits'] ?? 0, 2) }}</td>
            </tr>
            @if(($data['total_debits'] ?? 0) != ($data['total_credits'] ?? 0))
            <tr class="text-danger-600 dark:text-danger-400">
                <td class="py-2 px-4" colspan="2">Difference</td>
                <td class="py-2 px-4 text-right font-mono">
                    {{ number_format(abs(($data['total_debits'] ?? 0) - ($data['total_credits'] ?? 0)), 2) }}
                </td>
            </tr>
            @endif
        </tfoot>
    </table>
</div>
