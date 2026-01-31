<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Split;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Split>
 */
class SplitFactory extends Factory
{
    protected $model = Split::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 10000);
        $amountNum = (int) ($amount * 100);

        return [
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'amount_num' => $amountNum,
            'amount_denom' => 100,
            'value_num' => $amountNum,
            'value_denom' => 100,
            'action' => $this->faker->randomElement([Split::DEBIT, Split::CREDIT]),
            'memo' => $this->faker->optional()->sentence(3),
            'reconciled_state' => Split::RECONCILED_NOT,
            'reconcile_date' => null,
        ];
    }

    /**
     * Create a debit split.
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => Split::DEBIT,
        ]);
    }

    /**
     * Create a credit split.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => Split::CREDIT,
        ]);
    }

    /**
     * Set a specific amount.
     */
    public function amount(float $amount): static
    {
        $amountNum = (int) ($amount * 100);

        return $this->state(fn (array $attributes) => [
            'amount_num' => $amountNum,
            'amount_denom' => 100,
            'value_num' => $amountNum,
            'value_denom' => 100,
        ]);
    }

    /**
     * Mark as reconciled.
     */
    public function reconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciled_state' => Split::RECONCILED_YES,
            'reconcile_date' => now(),
        ]);
    }

    /**
     * Mark as cleared.
     */
    public function cleared(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciled_state' => Split::RECONCILED_CLEARED,
        ]);
    }

    /**
     * For a specific account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    /**
     * For a specific transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => $transaction->id,
        ]);
    }
}
