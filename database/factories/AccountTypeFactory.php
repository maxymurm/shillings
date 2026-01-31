<?php

namespace Database\Factories;

use App\Models\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountType>
 */
class AccountTypeFactory extends Factory
{
    protected $model = AccountType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                AccountType::ASSET,
                AccountType::LIABILITY,
                AccountType::EQUITY,
                AccountType::INCOME,
                AccountType::EXPENSE,
            ]),
            'normal_balance' => AccountType::DEBIT,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Asset account type.
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::ASSET,
            'normal_balance' => AccountType::DEBIT,
            'sort_order' => 1,
        ]);
    }

    /**
     * Liability account type.
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::LIABILITY,
            'normal_balance' => AccountType::CREDIT,
            'sort_order' => 2,
        ]);
    }

    /**
     * Equity account type.
     */
    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::EQUITY,
            'normal_balance' => AccountType::CREDIT,
            'sort_order' => 3,
        ]);
    }

    /**
     * Income account type.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::INCOME,
            'normal_balance' => AccountType::CREDIT,
            'sort_order' => 4,
        ]);
    }

    /**
     * Expense account type.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => AccountType::EXPENSE,
            'normal_balance' => AccountType::DEBIT,
            'sort_order' => 5,
        ]);
    }
}
