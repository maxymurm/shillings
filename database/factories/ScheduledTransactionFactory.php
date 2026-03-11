<?php

namespace Database\Factories;

use App\Models\ScheduledTransaction;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledTransactionFactory extends Factory
{
    protected $model = ScheduledTransaction::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $frequency = $this->faker->randomElement([
            ScheduledTransaction::FREQ_DAILY,
            ScheduledTransaction::FREQ_WEEKLY,
            ScheduledTransaction::FREQ_MONTHLY,
            ScheduledTransaction::FREQ_QUARTERLY,
            ScheduledTransaction::FREQ_YEARLY,
        ]);

        return [
            'company_id' => Company::factory(),
            'template_id' => null,
            'created_by_id' => null,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'currency_id' => function () {
                return Currency::where('code', 'KES')->first()?->id
                    ?? Currency::factory()->kes()->create()->id;
            },
            'splits_data' => null, // Will be set in configure()
            'frequency' => $frequency,
            'frequency_interval' => 1,
            'start_date' => $startDate,
            'end_date' => null,
            'next_occurrence' => $startDate,
            'last_occurrence' => null,
            'day_of_week' => $frequency === ScheduledTransaction::FREQ_WEEKLY ? $this->faker->numberBetween(0, 6) : null,
            'day_of_month' => $frequency === ScheduledTransaction::FREQ_MONTHLY ? $this->faker->numberBetween(1, 28) : null,
            'month_of_year' => $frequency === ScheduledTransaction::FREQ_YEARLY ? $this->faker->numberBetween(1, 12) : null,
            'auto_create' => false,
            'auto_post' => false,
            'is_active' => true,
            'is_paused' => false,
            'occurrences_created' => 0,
            'max_occurrences' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ScheduledTransaction $scheduledTransaction) {
            if ($scheduledTransaction->splits_data === null) {
                // Try to use existing accounts from the company to avoid unique constraint errors
                $existingAccounts = Account::where('company_id', $scheduledTransaction->company_id)->take(2)->get();
                
                if ($existingAccounts->count() >= 2) {
                    $debitAccount = $existingAccounts[0];
                    $creditAccount = $existingAccounts[1];
                } else {
                    // If no existing accounts, create them
                    // But we need account types first - check if they exist
                    $assetType = AccountType::where('name', 'ASSET')->first();
                    $expenseType = AccountType::where('name', 'EXPENSE')->first();
                    
                    if (!$assetType) {
                        $assetType = AccountType::factory()->asset()->create();
                    }
                    if (!$expenseType) {
                        $expenseType = AccountType::factory()->expense()->create();
                    }
                    
                    $debitAccount = Account::factory()->create([
                        'company_id' => $scheduledTransaction->company_id,
                        'account_type_id' => $assetType->id,
                    ]);
                    $creditAccount = Account::factory()->create([
                        'company_id' => $scheduledTransaction->company_id,
                        'account_type_id' => $expenseType->id,
                    ]);
                }
                
                $amount = $this->faker->randomFloat(2, 100, 10000);

                $scheduledTransaction->splits_data = json_encode([
                    [
                        'account_id' => $debitAccount->id,
                        'amount' => $amount,
                        'action' => 'DEBIT',
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'amount' => $amount,
                        'action' => 'CREDIT',
                    ],
                ]);
            }
        });
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ScheduledTransaction::FREQ_MONTHLY,
            'day_of_month' => 1,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => ScheduledTransaction::FREQ_WEEKLY,
            'day_of_week' => 1, // Monday
        ]);
    }

    public function autoCreate(): static
    {
        return $this->state(fn () => [
            'auto_create' => true,
        ]);
    }

    public function autoPost(): static
    {
        return $this->state(fn () => [
            'auto_create' => true,
            'auto_post' => true,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'is_paused' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function due(): static
    {
        return $this->state(fn () => [
            'next_occurrence' => now()->subDay(),
            'is_active' => true,
            'is_paused' => false,
        ]);
    }
}
