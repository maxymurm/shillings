<?php

namespace App\Validators;

use App\Models\Split;
use App\Models\Transaction;
use App\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * Split Balance Validator
 *
 * Validates that all splits in a transaction sum to zero,
 * ensuring double-entry accounting integrity.
 */
class SplitValidator
{
    /**
     * Validation errors from last check.
     *
     * @var array<string, mixed>
     */
    private array $errors = [];

    /**
     * Validate a transaction's splits balance to zero.
     */
    public function validate(Transaction $transaction): bool
    {
        $this->errors = [];

        $splits = $transaction->splits;

        if ($splits->isEmpty()) {
            $this->errors['splits'] = 'Transaction must have at least one split.';

            return false;
        }

        if ($splits->count() < 2) {
            $this->errors['splits'] = 'Transaction must have at least two splits for double-entry.';

            return false;
        }

        // Check if multi-currency
        $currencies = $splits->pluck('account.currency_id')->unique();

        if ($currencies->count() === 1) {
            return $this->validateSingleCurrency($splits);
        }

        return $this->validateMultiCurrency($transaction, $splits);
    }

    /**
     * Validate splits from array data (before creating models).
     *
     * @param  array<array{amount_num: int, amount_denom: int, account_id?: string}>  $splitsData
     */
    public function validateFromArray(array $splitsData): bool
    {
        $this->errors = [];

        if (count($splitsData) < 2) {
            $this->errors['splits'] = 'Transaction must have at least two splits for double-entry.';

            return false;
        }

        $total = Money::zero();

        foreach ($splitsData as $index => $split) {
            if (! isset($split['amount_num'], $split['amount_denom'])) {
                $this->errors["splits.{$index}"] = 'Split must have amount_num and amount_denom.';

                continue;
            }

            if ($split['amount_denom'] <= 0) {
                $this->errors["splits.{$index}.amount_denom"] = 'Denominator must be positive.';

                continue;
            }

            $amount = Money::fromFraction($split['amount_num'], $split['amount_denom']);
            $total = $total->add($amount);
        }

        if (! empty($this->errors)) {
            return false;
        }

        if (! $total->isZero()) {
            $this->errors['balance'] = [
                'message' => 'Transaction splits do not balance.',
                'imbalance' => $total->toDecimal(),
                'imbalance_formatted' => $total->format(),
            ];

            return false;
        }

        return true;
    }

    /**
     * Calculate the imbalance amount.
     */
    public function getImbalance(Transaction $transaction): Money
    {
        $total = Money::zero();

        foreach ($transaction->splits as $split) {
            $amount = Money::fromFraction($split->amount_num, $split->amount_denom);
            $total = $total->add($amount);
        }

        return $total;
    }

    /**
     * Calculate imbalance from array data.
     *
     * @param  array<array{amount_num: int, amount_denom: int}>  $splitsData
     */
    public function getImbalanceFromArray(array $splitsData): Money
    {
        $total = Money::zero();

        foreach ($splitsData as $split) {
            if (isset($split['amount_num'], $split['amount_denom']) && $split['amount_denom'] > 0) {
                $amount = Money::fromFraction($split['amount_num'], $split['amount_denom']);
                $total = $total->add($amount);
            }
        }

        return $total;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the last error message.
     */
    public function getErrorMessage(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstError = reset($this->errors);

        if (is_array($firstError)) {
            return $firstError['message'] ?? 'Validation failed.';
        }

        return $firstError;
    }

    /**
     * Calculate debit and credit totals.
     *
     * @return array{debits: Money, credits: Money}
     */
    public function getDebitsCreditsTotals(Transaction $transaction): array
    {
        $debits = Money::zero();
        $credits = Money::zero();

        foreach ($transaction->splits as $split) {
            $amount = Money::fromFraction($split->amount_num, $split->amount_denom);

            if ($amount->isPositive()) {
                $debits = $debits->add($amount);
            } else {
                $credits = $credits->add($amount->abs());
            }
        }

        return [
            'debits' => $debits,
            'credits' => $credits,
        ];
    }

    /**
     * Validate single-currency transaction.
     */
    private function validateSingleCurrency(Collection $splits): bool
    {
        $total = Money::zero();

        foreach ($splits as $split) {
            $amount = Money::fromFraction($split->amount_num, $split->amount_denom);
            $total = $total->add($amount);
        }

        if (! $total->isZero()) {
            $this->errors['balance'] = [
                'message' => 'Transaction splits do not balance.',
                'imbalance' => $total->toDecimal(),
                'imbalance_formatted' => $total->format(),
            ];

            return false;
        }

        return true;
    }

    /**
     * Validate multi-currency transaction.
     *
     * For multi-currency, we validate:
     * 1. Each currency group balances internally OR
     * 2. Value columns (in transaction currency) balance
     */
    private function validateMultiCurrency(Transaction $transaction, Collection $splits): bool
    {
        // Check if splits have value columns (transaction currency amounts)
        $hasValues = $splits->every(fn ($s) => $s->value_num !== null && $s->value_denom !== null);

        if ($hasValues) {
            // Validate using value columns (transaction currency)
            $total = Money::zero();

            foreach ($splits as $split) {
                $value = Money::fromFraction($split->value_num, $split->value_denom);
                $total = $total->add($value);
            }

            if (! $total->isZero()) {
                $this->errors['balance'] = [
                    'message' => 'Multi-currency transaction values do not balance.',
                    'imbalance' => $total->toDecimal(),
                    'imbalance_formatted' => $total->format(),
                    'currency' => $transaction->currency?->code,
                ];

                return false;
            }

            return true;
        }

        // Without value columns, we can't validate multi-currency balance
        // This is acceptable for import scenarios where rates will be added later
        $this->errors['multi_currency'] = [
            'message' => 'Multi-currency transaction requires value columns for balance validation.',
            'currencies' => $splits->pluck('account.currency.code')->unique()->values()->all(),
        ];

        return false;
    }

    /**
     * Auto-balance a transaction by adding/adjusting an imbalance split.
     *
     * @param  array<array{amount_num: int, amount_denom: int, account_id?: string}>  $splitsData
     * @param  string  $imbalanceAccountId  Account to post imbalance to
     * @return array<array{amount_num: int, amount_denom: int, account_id?: string}>
     */
    public function autoBalance(array $splitsData, string $imbalanceAccountId): array
    {
        $imbalance = $this->getImbalanceFromArray($splitsData);

        if ($imbalance->isZero()) {
            return $splitsData;
        }

        // Find existing split to imbalance account
        $existingIndex = null;
        foreach ($splitsData as $index => $split) {
            if (($split['account_id'] ?? null) === $imbalanceAccountId) {
                $existingIndex = $index;
                break;
            }
        }

        // Create balancing split (negate the imbalance)
        $balancingSplit = [
            'account_id' => $imbalanceAccountId,
            'amount_num' => -$imbalance->getNumerator(),
            'amount_denom' => $imbalance->getDenominator(),
            'memo' => 'Auto-balance adjustment',
        ];

        if ($existingIndex !== null) {
            // Adjust existing split
            $existingAmount = Money::fromFraction(
                $splitsData[$existingIndex]['amount_num'],
                $splitsData[$existingIndex]['amount_denom']
            );
            $newAmount = $existingAmount->subtract($imbalance);

            $splitsData[$existingIndex]['amount_num'] = $newAmount->getNumerator();
            $splitsData[$existingIndex]['amount_denom'] = $newAmount->getDenominator();
        } else {
            // Add new balancing split
            $splitsData[] = $balancingSplit;
        }

        return $splitsData;
    }
}
