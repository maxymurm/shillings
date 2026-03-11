<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetAccount>
 */
class BudgetAccountFactory extends Factory
{
    protected $model = BudgetAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'account_id' => Account::factory(),
            'period_num' => $this->faker->numberBetween(1, 12),
            'amount_num' => $this->faker->numberBetween(10000, 1000000), // 100.00 - 10,000.00
            'amount_denom' => 100,
        ];
    }

    /**
     * Set specific period.
     */
    public function period(int $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period_num' => $period,
        ]);
    }

    /**
     * Set specific amount.
     */
    public function amount(int $numerator, int $denominator = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_num' => $numerator,
            'amount_denom' => $denominator,
        ]);
    }
}
