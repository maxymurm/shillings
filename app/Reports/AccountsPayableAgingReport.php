<?php

namespace App\Reports;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\ValueObjects\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Accounts Payable Aging Report (Issue #102)
 *
 * Shows outstanding vendor bills grouped by aging buckets:
 * Current, 1-30, 31-60, 61-90, 90+ days overdue
 */
class AccountsPayableAgingReport extends BaseReport
{
    protected array $agingBuckets = [
        'current' => 0,
        '1_30_days' => 30,
        '31_60_days' => 60,
        '61_90_days' => 90,
        'over_90_days' => PHP_INT_MAX,
    ];

    public function getName(): string
    {
        return 'Accounts Payable Aging';
    }

    public function getDescription(): string
    {
        return 'Outstanding vendor bills grouped by age.';
    }

    /**
     * Generate the A/P aging report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $asOfDate = $this->getEndDate() ?? now();

        // Get all vendors with outstanding balances
        $vendors = Contact::query()
            ->where('company_id', $company->id)
            ->where('type', 'vendor')
            ->where('enabled', true)
            ->whereHas('documents', function ($query) {
                $query->where('type', 'bill')
                    ->whereIn('status', ['sent', 'partial']);
            })
            ->with(['documents' => function ($query) {
                $query->where('type', 'bill')
                    ->whereIn('status', ['sent', 'partial']);
            }])
            ->get();

        $contactsData = [];
        $totals = [
            'current' => Money::zero($this->getCurrencyCode()),
            '1_30_days' => Money::zero($this->getCurrencyCode()),
            '31_60_days' => Money::zero($this->getCurrencyCode()),
            '61_90_days' => Money::zero($this->getCurrencyCode()),
            'over_90_days' => Money::zero($this->getCurrencyCode()),
            'total' => Money::zero($this->getCurrencyCode()),
        ];

        foreach ($vendors as $vendor) {
            $vendorAging = $this->calculateContactAging($vendor, $asOfDate);

            if ($vendorAging['total'] > 0) {
                $contactsData[] = $vendorAging;

                // Add to totals
                foreach (array_keys($this->agingBuckets) as $bucket) {
                    $amount = Money::fromDecimal($vendorAging[$bucket], $this->getCurrencyCode());
                    $totals[$bucket] = $totals[$bucket]->add($amount);
                }
                $totals['total'] = $totals['total']->add(
                    Money::fromDecimal($vendorAging['total'], $this->getCurrencyCode())
                );
            }
        }

        $this->data = [
            'contacts' => $contactsData,
            'totals' => [
                'current' => $totals['current']->toDecimal(),
                'current_formatted' => $this->formatMoney($totals['current']->toDecimal()),
                '1_30_days' => $totals['1_30_days']->toDecimal(),
                '1_30_days_formatted' => $this->formatMoney($totals['1_30_days']->toDecimal()),
                '31_60_days' => $totals['31_60_days']->toDecimal(),
                '31_60_days_formatted' => $this->formatMoney($totals['31_60_days']->toDecimal()),
                '61_90_days' => $totals['61_90_days']->toDecimal(),
                '61_90_days_formatted' => $this->formatMoney($totals['61_90_days']->toDecimal()),
                'over_90_days' => $totals['over_90_days']->toDecimal(),
                'over_90_days_formatted' => $this->formatMoney($totals['over_90_days']->toDecimal()),
                'total' => $totals['total']->toDecimal(),
                'total_formatted' => $this->formatMoney($totals['total']->toDecimal()),
            ],
            'as_of_date' => $asOfDate->toDateString(),
            'contact_count' => count($contactsData),
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Calculate aging for a single contact.
     */
    protected function calculateContactAging(Contact $contact, Carbon $asOfDate): array
    {
        $buckets = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
        ];

        foreach ($contact->documents as $document) {
            if ($document->type !== 'bill') {
                continue;
            }

            $outstandingAmount = $document->total->subtract($document->amount_paid)->toDecimal();
            if ($outstandingAmount <= 0) {
                continue;
            }

            $dueDate = $document->due_at instanceof Carbon 
                ? $document->due_at 
                : Carbon::parse($document->due_at);
            
            $daysOverdue = $asOfDate->diffInDays($dueDate, false);

            // Negative means overdue
            $daysOverdue = $daysOverdue < 0 ? abs($daysOverdue) : 0;

            // Assign to appropriate bucket
            if ($daysOverdue === 0 || $dueDate->isFuture()) {
                $buckets['current'] += $outstandingAmount;
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30_days'] += $outstandingAmount;
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60_days'] += $outstandingAmount;
            } elseif ($daysOverdue <= 90) {
                $buckets['61_90_days'] += $outstandingAmount;
            } else {
                $buckets['over_90_days'] += $outstandingAmount;
            }
        }

        $total = array_sum($buckets);

        return [
            'contact_id' => $contact->id,
            'contact_name' => $contact->name,
            'contact_email' => $contact->email,
            'current' => $buckets['current'],
            'current_formatted' => $this->formatMoney($buckets['current']),
            '1_30_days' => $buckets['1_30_days'],
            '1_30_days_formatted' => $this->formatMoney($buckets['1_30_days']),
            '31_60_days' => $buckets['31_60_days'],
            '31_60_days_formatted' => $this->formatMoney($buckets['31_60_days']),
            '61_90_days' => $buckets['61_90_days'],
            '61_90_days_formatted' => $this->formatMoney($buckets['61_90_days']),
            'over_90_days' => $buckets['over_90_days'],
            'over_90_days_formatted' => $this->formatMoney($buckets['over_90_days']),
            'total' => $total,
            'total_formatted' => $this->formatMoney($total),
        ];
    }
}
