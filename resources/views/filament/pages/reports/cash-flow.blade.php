<div class="space-y-6">
    {{-- Operating Activities --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Cash Flows from Operating Activities</h3>
        <table class="w-full text-sm">
            <tbody>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4">Net Income</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['net_income'] ?? 0, 2) }}</td>
                </tr>
                @foreach($data['operating_adjustments'] ?? [] as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4 pl-8">{{ $item['name'] }}</td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ $item['amount'] < 0 ? '(' . number_format(abs($item['amount']), 2) . ')' : number_format($item['amount'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Net Cash from Operating Activities</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['operating_cash_flow'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Investing Activities --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Cash Flows from Investing Activities</h3>
        <table class="w-full text-sm">
            <tbody>
                @forelse($data['investing_activities'] ?? [] as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4">{{ $item['name'] }}</td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ $item['amount'] < 0 ? '(' . number_format(abs($item['amount']), 2) . ')' : number_format($item['amount'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="py-2 px-4 text-gray-500" colspan="2">No investing activities</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Net Cash from Investing Activities</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['investing_cash_flow'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Financing Activities --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Cash Flows from Financing Activities</h3>
        <table class="w-full text-sm">
            <tbody>
                @forelse($data['financing_activities'] ?? [] as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4">{{ $item['name'] }}</td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ $item['amount'] < 0 ? '(' . number_format(abs($item['amount']), 2) . ')' : number_format($item['amount'], 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td class="py-2 px-4 text-gray-500" colspan="2">No financing activities</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Net Cash from Financing Activities</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['financing_cash_flow'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Summary --}}
    <div class="border-t-2 pt-4 space-y-2">
        <table class="w-full">
            <tbody>
                <tr>
                    <td class="py-2 px-4 font-semibold">Net Change in Cash</td>
                    <td class="py-2 px-4 text-right font-mono font-semibold">
                        @php $netChange = ($data['operating_cash_flow'] ?? 0) + ($data['investing_cash_flow'] ?? 0) + ($data['financing_cash_flow'] ?? 0); @endphp
                        {{ $netChange < 0 ? '(' . number_format(abs($netChange), 2) . ')' : number_format($netChange, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 px-4">Beginning Cash Balance</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['beginning_cash'] ?? 0, 2) }}</td>
                </tr>
                <tr class="font-bold text-lg">
                    <td class="py-3 px-4">Ending Cash Balance</td>
                    <td class="py-3 px-4 text-right font-mono">{{ number_format($data['ending_cash'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
