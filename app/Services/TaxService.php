<?php

namespace App\Services;

use App\Models\Tax;
use App\Models\TaxRule;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Company;
use App\Models\DocumentItem;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaxService
{
    /**
     * Get applicable taxes for a document item.
     */
    public function getApplicableTaxes(
        Company $company,
        string $documentType,
        ?Account $account = null,
        ?Contact $contact = null
    ): Collection {
        $appliesTo = in_array($documentType, ['invoice', 'quote', 'credit_note']) 
            ? 'sales' 
            : 'purchases';

        $query = Tax::where('company_id', $company->id)
            ->where('enabled', true);

        // Check for specific rules
        $taxIds = TaxRule::query()
            ->whereHas('tax', fn ($q) => $q->where('company_id', $company->id)->where('enabled', true))
            ->where('applies_to', $appliesTo)
            ->when($account, function ($q) use ($account) {
                $q->where(function ($q2) use ($account) {
                    $q2->whereNull('account_id')
                        ->orWhere('account_id', $account->id);
                });
            })
            ->when($contact, function ($q) use ($contact) {
                $q->where(function ($q2) use ($contact) {
                    $q2->whereNull('contact_type')
                        ->orWhere('contact_type', $contact->type);
                });
            })
            ->orderBy('priority')
            ->pluck('tax_id')
            ->unique();

        if ($taxIds->isNotEmpty()) {
            return Tax::whereIn('id', $taxIds)->get();
        }

        // Return default taxes if no specific rules match
        return $query->whereDoesntHave('rules')->get();
    }

    /**
     * Calculate tax for an amount.
     */
    public function calculateTax(Money $amount, Tax $tax): Money
    {
        return $tax->calculate($amount);
    }

    /**
     * Calculate tax for inclusive amount (extract tax from total).
     */
    public function calculateTaxInclusive(Money $total, Tax $tax): Money
    {
        return $tax->calculateInclusive($total);
    }

    /**
     * Calculate taxes for a document item (handles compound taxes).
     */
    public function calculateItemTaxes(Money $subtotal, Collection $taxes): array
    {
        $currency = $subtotal->getCurrency();
        $taxAmounts = [];
        $totalTax = Money::zero($currency);
        $runningSubtotal = $subtotal;

        // Sort: non-compound first, then compound
        $sortedTaxes = $taxes->sortBy('is_compound');

        foreach ($sortedTaxes as $tax) {
            $baseAmount = $tax->is_compound ? $runningSubtotal->add($totalTax) : $subtotal;
            $taxAmount = $tax->calculate($baseAmount);
            
            $taxAmounts[$tax->id] = [
                'tax' => $tax,
                'base_amount' => $baseAmount,
                'tax_amount' => $taxAmount,
            ];
            
            $totalTax = $totalTax->add($taxAmount);
        }

        return [
            'subtotal' => $subtotal,
            'taxes' => $taxAmounts,
            'total_tax' => $totalTax,
            'total' => $subtotal->add($totalTax),
        ];
    }

    /**
     * Get tax summary for a company (for tax returns).
     */
    public function getTaxSummary(Company $company, string $startDate, string $endDate): array
    {
        $currency = $company->currency_code ?? 'KES';
        
        // Get all document items with taxes in the period
        $items = DocumentItem::query()
            ->whereHas('document', function ($q) use ($company, $startDate, $endDate) {
                $q->where('company_id', $company->id)
                    ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
                    ->whereBetween('issue_date', [$startDate, $endDate]);
            })
            ->whereNotNull('tax_id')
            ->with(['tax', 'document'])
            ->get();

        $salesTax = [];
        $purchaseTax = [];

        foreach ($items as $item) {
            $tax = $item->tax;
            $taxAmount = Money::fromFraction($item->tax_amount_num, $item->tax_amount_denom, $currency);
            $documentType = $item->document->type;
            
            $isSales = in_array($documentType, ['invoice', 'debit_note']);
            
            $key = $tax->id;
            $collection = $isSales ? 'salesTax' : 'purchaseTax';
            
            if (!isset($$collection[$key])) {
                $$collection[$key] = [
                    'tax' => $tax,
                    'taxable_amount' => Money::zero($currency),
                    'tax_amount' => Money::zero($currency),
                    'transaction_count' => 0,
                ];
            }
            
            $$collection[$key]['taxable_amount'] = $$collection[$key]['taxable_amount']->add(
                Money::fromFraction($item->total_num - $item->tax_amount_num, $item->total_denom, $currency)
            );
            $$collection[$key]['tax_amount'] = $$collection[$key]['tax_amount']->add($taxAmount);
            $$collection[$key]['transaction_count']++;
        }

        // Calculate totals
        $totalSalesTax = Money::zero($currency);
        $totalPurchaseTax = Money::zero($currency);

        foreach ($salesTax as $entry) {
            $totalSalesTax = $totalSalesTax->add($entry['tax_amount']);
        }

        foreach ($purchaseTax as $entry) {
            $totalPurchaseTax = $totalPurchaseTax->add($entry['tax_amount']);
        }

        $netTax = $totalSalesTax->subtract($totalPurchaseTax);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'sales_taxes' => array_values($salesTax),
            'purchase_taxes' => array_values($purchaseTax),
            'totals' => [
                'sales_tax' => $totalSalesTax,
                'purchase_tax' => $totalPurchaseTax,
                'net_tax' => $netTax,
                'is_payable' => $netTax->isPositive(),
            ],
        ];
    }

    /**
     * Create a new tax with optional rules.
     */
    public function create(array $data, ?array $rules = null): Tax
    {
        return DB::transaction(function () use ($data, $rules) {
            $tax = Tax::create($data);

            if ($rules) {
                foreach ($rules as $ruleData) {
                    $ruleData['tax_id'] = $tax->id;
                    TaxRule::create($ruleData);
                }
            }

            return $tax->fresh(['rules']);
        });
    }

    /**
     * Update a tax with rules.
     */
    public function update(Tax $tax, array $data, ?array $rules = null): Tax
    {
        return DB::transaction(function () use ($tax, $data, $rules) {
            $tax->update($data);

            if ($rules !== null) {
                // Replace all rules
                $tax->rules()->delete();
                foreach ($rules as $ruleData) {
                    $ruleData['tax_id'] = $tax->id;
                    TaxRule::create($ruleData);
                }
            }

            return $tax->fresh(['rules']);
        });
    }

    /**
     * Get taxes for a specific region/jurisdiction.
     */
    public function getTaxesForRegion(Company $company, string $region): Collection
    {
        return Tax::where('company_id', $company->id)
            ->where('enabled', true)
            ->whereHas('rules', function ($q) use ($region) {
                $q->where('region', $region);
            })
            ->get();
    }

    /**
     * Get recoverable tax (VAT input tax).
     */
    public function getRecoverableTax(Company $company, string $startDate, string $endDate): Money
    {
        $currency = $company->currency_code ?? 'KES';
        $total = Money::zero($currency);

        $items = DocumentItem::query()
            ->whereHas('document', function ($q) use ($company, $startDate, $endDate) {
                $q->where('company_id', $company->id)
                    ->whereIn('type', ['bill', 'credit_note'])
                    ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
                    ->whereBetween('issue_date', [$startDate, $endDate]);
            })
            ->whereHas('tax', fn ($q) => $q->where('is_recoverable', true))
            ->get();

        foreach ($items as $item) {
            $total = $total->add(
                Money::fromFraction($item->tax_amount_num, $item->tax_amount_denom, $currency)
            );
        }

        return $total;
    }

    /**
     * Validate tax number format (basic validation).
     */
    public function validateTaxNumber(string $taxNumber, string $country = 'KE'): bool
    {
        $patterns = [
            'KE' => '/^P\d{9}[A-Z]$/', // Kenya PIN format
            'US' => '/^\d{2}-\d{7}$/', // US EIN format
            'GB' => '/^GB\d{9}$|^GB\d{12}$|^GBGD\d{3}$|^GBHA\d{3}$/', // UK VAT
            'EU' => '/^[A-Z]{2}\d{8,12}$/', // Generic EU VAT
        ];

        $pattern = $patterns[$country] ?? '/^.{5,20}$/';
        return (bool) preg_match($pattern, $taxNumber);
    }
}
