<?php

namespace App\Reports;

use App\Models\Account;
use App\Models\Split;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

/**
 * General Ledger Report (Issue #33)
 *
 * Shows all accounts with their transactions for a period.
 * Organized by account type with opening balances, transactions, and closing balances.
 */
class GeneralLedgerReport extends BaseReport
{
    protected ?array $accountTypes = null;

    protected ?array $accountIds = null;

    public function getName(): string
    {
        return 'General Ledger';
    }

    public function getDescription(): string
    {
        return 'Complete listing of all accounts and their transactions for the period.';
    }

    /**
     * Filter by specific account types.
     */
    public function forAccountTypes(array $types): self
    {
        $this->accountTypes = $types;

        return $this;
    }

    /**
     * Filter by specific account IDs.
     */
    public function forAccounts(array $accountIds): self
    {
        $this->accountIds = $accountIds;

        return $this;
    }

    /**
     * Generate the general ledger report.
     */
    public function generate(): array
    {
        $company = $this->getCompany();
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        // Get accounts grouped by type
        $query = Account::query()
            ->where('company_id', $company->id)
            ->where('is_placeholder', false)
            ->with(['accountType', 'currency']);

        if ($this->accountTypes) {
            $query->whereHas('accountType', fn ($q) => $q->whereIn('name', $this->accountTypes));
        }

        if ($this->accountIds) {
            $query->whereIn('id', $this->accountIds);
        }

        $accounts = $query->orderBy('code')->get();

        $ledgerData = [];
        $totals = [
            'opening_debit' => Money::zero($this->getCurrencyCode()),
            'opening_credit' => Money::zero($this->getCurrencyCode()),
            'period_debit' => Money::zero($this->getCurrencyCode()),
            'period_credit' => Money::zero($this->getCurrencyCode()),
            'closing_debit' => Money::zero($this->getCurrencyCode()),
            'closing_credit' => Money::zero($this->getCurrencyCode()),
        ];

        foreach ($accounts as $account) {
            $accountData = $this->generateAccountLedger($account, $startDate, $endDate);

            if ($accountData['transaction_count'] > 0 || $accountData['opening_balance'] != 0) {
                $ledgerData[] = $accountData;

                // Add to totals
                $opening = Money::fromDecimal($accountData['opening_balance'], $this->getCurrencyCode());
                if ($accountData['opening_balance'] >= 0) {
                    $totals['opening_debit'] = $totals['opening_debit']->add($opening);
                } else {
                    $totals['opening_credit'] = $totals['opening_credit']->add($opening->abs());
                }

                $totals['period_debit'] = $totals['period_debit']->add(
                    Money::fromDecimal($accountData['period_debit'], $this->getCurrencyCode())
                );
                $totals['period_credit'] = $totals['period_credit']->add(
                    Money::fromDecimal($accountData['period_credit'], $this->getCurrencyCode())
                );

                $closing = Money::fromDecimal($accountData['closing_balance'], $this->getCurrencyCode());
                if ($accountData['closing_balance'] >= 0) {
                    $totals['closing_debit'] = $totals['closing_debit']->add($closing);
                } else {
                    $totals['closing_credit'] = $totals['closing_credit']->add($closing->abs());
                }
            }
        }

        $this->data = [
            'accounts' => $ledgerData,
            'totals' => [
                'opening_debit' => $totals['opening_debit']->toDecimal(),
                'opening_debit_formatted' => $this->formatMoney($totals['opening_debit']->toDecimal()),
                'opening_credit' => $totals['opening_credit']->toDecimal(),
                'opening_credit_formatted' => $this->formatMoney($totals['opening_credit']->toDecimal()),
                'period_debit' => $totals['period_debit']->toDecimal(),
                'period_debit_formatted' => $this->formatMoney($totals['period_debit']->toDecimal()),
                'period_credit' => $totals['period_credit']->toDecimal(),
                'period_credit_formatted' => $this->formatMoney($totals['period_credit']->toDecimal()),
                'closing_debit' => $totals['closing_debit']->toDecimal(),
                'closing_debit_formatted' => $this->formatMoney($totals['closing_debit']->toDecimal()),
                'closing_credit' => $totals['closing_credit']->toDecimal(),
                'closing_credit_formatted' => $this->formatMoney($totals['closing_credit']->toDecimal()),
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'account_count' => count($ledgerData),
        ];

        $this->generated = true;

        return $this->data;
    }

    /**
     * Generate ledger for a single account.
     */
    protected function generateAccountLedger(Account $account, $startDate, $endDate): array
    {
        // Calculate opening balance (all transactions before start date)
        $openingBalance = $this->getAccountBalanceAsOf($account, $startDate->copy()->subDay());

        // Get transactions for the period
        $transactions = $this->getAccountTransactions($account, $startDate, $endDate);

        // Calculate period totals
        $periodDebit = Money::zero($this->getCurrencyCode());
        $periodCredit = Money::zero($this->getCurrencyCode());

        $transactionData = [];
        $runningBalance = $openingBalance;

        foreach ($transactions as $txn) {
            $amount = Money::fromFraction($txn->amount_num, $txn->amount_denom, $this->getCurrencyCode());

            if ($txn->action === Split::DEBIT) {
                $periodDebit = $periodDebit->add($amount);
                $runningBalance = $runningBalance->add($amount);
            } else {
                $periodCredit = $periodCredit->add($amount);
                $runningBalance = $runningBalance->subtract($amount);
            }

            $transactionData[] = [
                'date' => $txn->transaction_date,
                'description' => $txn->description,
                'memo' => $txn->memo,
                'reference' => $txn->num,
                'debit' => $txn->action === Split::DEBIT ? $amount->toDecimal() : null,
                'debit_formatted' => $txn->action === Split::DEBIT ? $this->formatMoney($amount->toDecimal()) : null,
                'credit' => $txn->action === Split::CREDIT ? $amount->toDecimal() : null,
                'credit_formatted' => $txn->action === Split::CREDIT ? $this->formatMoney($amount->toDecimal()) : null,
                'balance' => $runningBalance->toDecimal(),
                'balance_formatted' => $this->formatMoney($runningBalance->toDecimal()),
            ];
        }

        $closingBalance = $openingBalance->add($periodDebit)->subtract($periodCredit);

        return [
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'account_full_name' => $account->full_name ?? $account->name,
            'account_type' => $account->accountType?->name,
            'currency_code' => $account->currency?->code ?? $this->getCurrencyCode(),
            'opening_balance' => $openingBalance->toDecimal(),
            'opening_balance_formatted' => $this->formatMoney($openingBalance->toDecimal()),
            'period_debit' => $periodDebit->toDecimal(),
            'period_debit_formatted' => $this->formatMoney($periodDebit->toDecimal()),
            'period_credit' => $periodCredit->toDecimal(),
            'period_credit_formatted' => $this->formatMoney($periodCredit->toDecimal()),
            'closing_balance' => $closingBalance->toDecimal(),
            'closing_balance_formatted' => $this->formatMoney($closingBalance->toDecimal()),
            'transactions' => $transactionData,
            'transaction_count' => count($transactionData),
        ];
    }

    /**
     * Get account balance as of a specific date.
     */
    protected function getAccountBalanceAsOf(Account $account, $asOfDate): Money
    {
        $result = DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where(function ($query) use ($asOfDate) {
                $query->whereDate('transactions.post_date', '<=', $asOfDate)
                    ->orWhere(function ($q) use ($asOfDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereDate('transactions.transaction_date', '<=', $asOfDate);
                    });
            })
            ->selectRaw('
                SUM(CASE WHEN splits.action = ? THEN splits.amount_num ELSE -splits.amount_num END) as net_num,
                MAX(splits.amount_denom) as denom
            ', [Split::DEBIT])
            ->first();

        if (! $result || ! $result->denom) {
            return Money::zero($this->getCurrencyCode());
        }

        return Money::fromFraction((int) ($result->net_num ?? 0), (int) $result->denom, $this->getCurrencyCode());
    }

    /**
     * Get transactions for an account within a date range.
     */
    protected function getAccountTransactions(Account $account, $startDate, $endDate)
    {
        return DB::table('splits')
            ->join('transactions', 'splits.transaction_id', '=', 'transactions.id')
            ->where('splits.account_id', $account->id)
            ->where('transactions.is_void', false)
            ->where('transactions.is_posted', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transactions.post_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('transactions.post_date')
                            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
                    });
            })
            ->select([
                'splits.id as split_id',
                'splits.action',
                'splits.amount_num',
                'splits.amount_denom',
                'splits.memo',
                'transactions.transaction_date',
                'transactions.post_date',
                'transactions.description',
                'transactions.num',
            ])
            ->orderBy('transactions.post_date')
            ->orderBy('transactions.transaction_date')
            ->orderBy('transactions.created_at')
            ->get();
    }
}
