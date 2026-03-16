<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * Trial Balance Report
 *
 * Lists all accounts with their debit and credit balances.
 * Total debits must equal total credits.
 */
class TrialBalanceReport extends BaseReport
{
    public function getName(): string
    {
        return 'Trial Balance';
    }

    public function getDescription(): string
    {
        return 'Shows all account balances with debit and credit columns. Totals must balance.';
    }

    /**
     * Generate the trial balance report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $endDate = $this->getEndDate();

        // Get all accounts with their balances
        $accounts = Account::query()
            ->where('company_id', $company->id)
            ->where('is_placeholder', false)
            ->with(['accountType', 'currency'])
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalDebits = Money::zero($this->getCurrencyCode());
        $totalCredits = Money::zero($this->getCurrencyCode());

        foreach ($accounts as $account) {
            // Calculate balance from splits up to end date
            $balance = $this->getAccountBalance($account, $endDate);

            if ($balance->isZero()) {
                continue; // Skip zero-balance accounts
            }

            // Determine if debit or credit based on account type and balance
            $isDebitNormal = in_array($account->accountType->name, ['Asset', 'Expense']);
            $isPositive = $balance->isPositive();

            // Debit-normal accounts: positive = debit, negative = credit
            // Credit-normal accounts: positive = credit, negative = debit
            if ($isDebitNormal) {
                if ($isPositive) {
                    $debit = $balance;
                    $credit = Money::zero($this->getCurrencyCode());
                } else {
                    $debit = Money::zero($this->getCurrencyCode());
                    $credit = $balance->negate();
                }
            } else {
                if ($isPositive) {
                    $debit = Money::zero($this->getCurrencyCode());
                    $credit = $balance;
                } else {
                    $debit = $balance->negate();
                    $credit = Money::zero($this->getCurrencyCode());
                }
            }

            $rows[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->accountType->name,
                'debit' => $debit->toDecimal(),
                'credit' => $credit->toDecimal(),
                'debit_formatted' => $debit->isZero() ? '' : $this->formatMoney($debit->toDecimal()),
                'credit_formatted' => $credit->isZero() ? '' : $this->formatMoney($credit->toDecimal()),
            ];

            $totalDebits = $totalDebits->add($debit);
            $totalCredits = $totalCredits->add($credit);
        }

        // Check if balanced
        $difference = $totalDebits->subtract($totalCredits);
        $isBalanced = $difference->isZero();

        if (! $isBalanced) {
            $this->errors[] = "Trial balance is out of balance by {$difference->toDecimal()}";
        }

        $this->data = [
            'rows' => $rows,
            'totals' => [
                'debit' => $totalDebits->toDecimal(),
                'credit' => $totalCredits->toDecimal(),
                'debit_formatted' => $this->formatMoney($totalDebits->toDecimal()),
                'credit_formatted' => $this->formatMoney($totalCredits->toDecimal()),
                'difference' => $difference->toDecimal(),
                'is_balanced' => $isBalanced,
            ],
            'account_count' => count($rows),
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Get account balance as of a date.
     */
    private function getAccountBalance(Account $account, $endDate): Money
    {
        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.posted_at')
            ->where(function ($query) use ($endDate) {
                $query->whereDate('transactions.post_date', '<=', $endDate)
                    ->orWhere(function ($q) use ($endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereDate('transactions.transaction_date', '<=', $endDate);
                    });
            })
            ->selectRaw('
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE 0 END) as debit_num,
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE 0 END) as credit_num,
                MAX(splits.amount_denom) as denom
            ', [Split::DEBIT, Split::CREDIT])
            ->first();

        if (! $result || ! $result->denom) {
            return Money::zero($this->getCurrencyCode());
        }

        $netNum = ($result->debit_num ?? 0) - ($result->credit_num ?? 0);

        return Money::fromFraction((int) $netNum, (int) $result->denom, $this->getCurrencyCode());
    }
}
