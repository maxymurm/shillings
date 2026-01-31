<div class="space-y-6">
    {{-- Assets Section --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Assets</h3>
        <table class="w-full text-sm">
            <tbody>
                @foreach($data['assets'] ?? [] as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4" style="padding-left: {{ ($account['depth'] ?? 0) * 20 + 16 }}px">
                        @if($account['code'])
                            <span class="text-gray-500">[{{ $account['code'] }}]</span>
                        @endif
                        {{ $account['name'] }}
                    </td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format($account['balance'] ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Total Assets</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['total_assets'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Liabilities Section --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Liabilities</h3>
        <table class="w-full text-sm">
            <tbody>
                @foreach($data['liabilities'] ?? [] as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4" style="padding-left: {{ ($account['depth'] ?? 0) * 20 + 16 }}px">
                        @if($account['code'])
                            <span class="text-gray-500">[{{ $account['code'] }}]</span>
                        @endif
                        {{ $account['name'] }}
                    </td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format($account['balance'] ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Total Liabilities</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['total_liabilities'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Equity Section --}}
    <div>
        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Equity</h3>
        <table class="w-full text-sm">
            <tbody>
                @foreach($data['equity'] ?? [] as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="py-2 px-4" style="padding-left: {{ ($account['depth'] ?? 0) * 20 + 16 }}px">
                        @if($account['code'])
                            <span class="text-gray-500">[{{ $account['code'] }}]</span>
                        @endif
                        {{ $account['name'] }}
                    </td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format($account['balance'] ?? 0, 2) }}
                    </td>
                </tr>
                @endforeach
                @if(isset($data['retained_earnings']))
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 italic">
                    <td class="py-2 px-4">Retained Earnings</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['retained_earnings'], 2) }}</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="border-t font-semibold">
                    <td class="py-2 px-4">Total Equity</td>
                    <td class="py-2 px-4 text-right font-mono">{{ number_format($data['total_equity'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Summary --}}
    <div class="border-t-2 pt-4">
        <table class="w-full text-sm">
            <tbody>
                <tr class="font-bold text-lg">
                    <td class="py-2 px-4">Total Liabilities & Equity</td>
                    <td class="py-2 px-4 text-right font-mono">
                        {{ number_format(($data['total_liabilities'] ?? 0) + ($data['total_equity'] ?? 0), 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
        
        @php
            $difference = ($data['total_assets'] ?? 0) - (($data['total_liabilities'] ?? 0) + ($data['total_equity'] ?? 0));
        @endphp
        
        @if(abs($difference) > 0.01)
        <div class="mt-4 p-3 bg-danger-50 dark:bg-danger-900/20 text-danger-700 dark:text-danger-400 rounded-lg">
            <strong>Warning:</strong> Balance sheet is out of balance by {{ number_format(abs($difference), 2) }}
        </div>
        @else
        <div class="mt-4 p-3 bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400 rounded-lg">
            ✓ Balance sheet is in balance
        </div>
        @endif
    </div>
</div>
