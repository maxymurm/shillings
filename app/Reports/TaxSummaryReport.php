<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\Split;
use App\Models\Tax;
use App\Models\Transaction;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Tax Summary Report (Issue #103)
 *
 * Shows tax collected and paid by period, grouped by tax rate.
 */
class TaxSummaryReport extends BaseReport
{
    public function getName(): string
    {
        return 'Tax Summary';
    }

    public function getDescription(): string
    {
        return 'Summary of taxes collected and paid by tax rate and period.';
    }

    /**
     * Generate the tax summary report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Get all taxes for this company
        $taxes = Tax::where('company_id', $company->id)->get();

        $taxData = [];
        $totals = [
            'collected' => Money::zero($this->getCurrencyCode()),
            'paid' => Money::zero($this->getCurrencyCode()),
            'net' => Money::zero($this->getCurrencyCode()),
        ];

        foreach ($taxes as $tax) {
            $taxSummary = $this->calculateTaxSummary($tax, $startDate, $endDate);

            $taxData[] = $taxSummary;

            // Add to totals
            $collected = Money::fromDecimal($taxSummary['collected'], $this->getCurrencyCode());
            $paid = Money::fromDecimal($taxSummary['paid'], $this->getCurrencyCode());
            
            $totals['collected'] = $totals['collected']->add($collected);
            $totals['paid'] = $totals['paid']->add($paid);
        }

        $totals['net'] = $totals['collected']->subtract($totals['paid']);

        $this->data = [
            'taxes' => $taxData,
            'totals' => [
                'collected' => $totals['collected']->toDecimal(),
                'collected_formatted' => $this->formatMoney($totals['collected']->toDecimal()),
                'paid' => $totals['paid']->toDecimal(),
                'paid_formatted' => $this->formatMoney($totals['paid']->toDecimal()),
                'net' => $totals['net']->toDecimal(),
                'net_formatted' => $this->formatMoney($totals['net']->toDecimal()),
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'tax_count' => count($taxData),
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Calculate tax summary for a single tax.
     */
    protected function calculateTaxSummary(Tax $tax, $startDate, $endDate): array
    {
        // Tax collected (on revenue/sales - credit splits with this tax)
        $collected = Split::query()
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->join('accounts', 'splits.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('splits.tax_id', $tax->id)
            ->where('transactions.company_id', $tax->company_id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where('splits.action', Split::CREDIT)
            ->whereIn('account_types.name', ['INCOME'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transactions.post_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                    });
            })
            ->sum(DB::raw('splits.amount_num / splits.amount_denom'));

        // Tax paid (on expenses - debit splits with this tax)
        $paid = Split::query()
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->join('accounts', 'splits.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('splits.tax_id', $tax->id)
            ->where('transactions.company_id', $tax->company_id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where('splits.action', Split::DEBIT)
            ->whereIn('account_types.name', ['EXPENSE'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transactions.post_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                    });
            })
            ->sum(DB::raw('splits.amount_num / splits.amount_denom'));

        $collected = (float) ($collected ?? 0);
        $paid = (float) ($paid ?? 0);
        $net = $collected - $paid;

        return [
            'tax_id' => $tax->id,
            'tax_name' => $tax->name,
            'tax_rate' => $tax->rate,
            'tax_type' => $tax->type,
            'collected' => $collected,
            'collected_formatted' => $this->formatMoney($collected),
            'paid' => $paid,
            'paid_formatted' => $this->formatMoney($paid),
            'net' => $net,
            'net_formatted' => $this->formatMoney($net),
        ];
    }
}
