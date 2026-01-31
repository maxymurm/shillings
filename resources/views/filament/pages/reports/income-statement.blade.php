<div class="space-y-6">
    {{-- Income Section --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Income</h3>
        <table class="w-full text-sm">
            <tbody>
                @foreach($data['income'] ?? [] as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4" style="padding-left: {{ ($account['depth'] ?? 0) * 20 + 16 }}px">
                        @if($account['code'])
                            <span class="text-gray-500">[{{ $account['code'] }}]</span>
                        @endif
                        {{ $account['name'] }}
                    </td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format($account['amount'] ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold text-success-600 dark:text-success-400">
                    <td class="py-2 px-4">Total Income</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['total_income'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Expenses Section --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Expenses</h3>
        <table class="w-full text-sm">
            <tbody>
                @foreach($data['expenses'] ?? [] as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4" style="padding-left: {{ ($account['depth'] ?? 0) * 20 + 16 }}px">
                        @if($account['code'])
                            <span class="text-gray-500">[{{ $account['code'] }}]</span>
                        @endif
                        {{ $account['name'] }}
                    </td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format($account['amount'] ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold text-danger-600 dark:text-danger-400">
                    <td class="py-2 px-4">Total Expenses</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['total_expenses'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Net Income Summary --}}
    <div class="border-t-2 pt-4">
        @php
            $netIncome = ($data['total_income'] ?? 0) - ($data['total_expenses'] ?? 0);
            $isProfit = $netIncome >= 0;
        @endphp
        
        <table class="w-full">
            <tbody>
                <tr class="font-bold text-xl {{ $isProfit ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    <td class="py-3 px-4">{{ $isProfit ? 'Net Income' : 'Net Loss' }}</td>
                    <td class="py-3 px-4 text-right font-mono">
                        {{ $isProfit ? '' : '(' }}{{ number_format(abs($netIncome), 2) }}{{ $isProfit ? '' : ')' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
