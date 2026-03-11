<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'account_type_id' => AccountType::factory(),
            'currency_id' => function () {
                return Currency::where('code', 'KES')->first()?->id
                    ?? Currency::factory()->kes()->create()->id;
            },
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'is_placeholder' => false,
            'is_hidden' => false,
            'path' => null,
            'level' => 0,
        ];
    }

    /**
     * Mark as placeholder account (can have children but not transactions).
     */
    public function placeholder(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_placeholder' => true,
        ]);
    }

    /**
     * Mark as hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    /**
     * Set as child of another account.
     */
    public function childOf(Account $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'company_id' => $parent->company_id,
            'currency_id' => $parent->currency_id,
            'account_type_id' => $parent->account_type_id,
        ]);
    }

    /**
     * Asset account.
     */
    public function asset(): static
    {
        return $this->state(function (array $attributes) {
            $assetType = AccountType::where('name', AccountType::ASSET)->first()
                ?? AccountType::factory()->asset()->create();

            return [
                'account_type_id' => $assetType->id,
            ];
        });
    }

    /**
     * Liability account.
     */
    public function liability(): static
    {
        return $this->state(function (array $attributes) {
            $type = AccountType::where('name', AccountType::LIABILITY)->first()
                ?? AccountType::factory()->liability()->create();

            return [
                'account_type_id' => $type->id,
            ];
        });
    }

    /**
     * Income account.
     */
    public function income(): static
    {
        return $this->state(function (array $attributes) {
            $type = AccountType::where('name', AccountType::INCOME)->first()
                ?? AccountType::factory()->income()->create();

            return [
                'account_type_id' => $type->id,
            ];
        });
    }

    /**
     * Expense account.
     */
    public function expense(): static
    {
        return $this->state(function (array $attributes) {
            $type = AccountType::where('name', AccountType::EXPENSE)->first()
                ?? AccountType::factory()->expense()->create();

            return [
                'account_type_id' => $type->id,
            ];
        });
    }

    /**
     * Equity account.
     */
    public function equity(): static
    {
        return $this->state(function (array $attributes) {
            $type = AccountType::where('name', AccountType::EQUITY)->first()
                ?? AccountType::factory()->equity()->create();

            return [
                'account_type_id' => $type->id,
            ];
        });
    }
}
