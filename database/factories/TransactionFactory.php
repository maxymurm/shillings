<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'post_date' => null,
            'description' => $this->faker->sentence(4),
            'num' => $this->faker->optional()->numerify('CHK-####'),
            'notes' => $this->faker->optional()->paragraph(),
            'is_posted' => false,
            'created_by' => null,
        ];
    }

    /**
     * Mark as posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_posted' => true,
            'post_date' => $attributes['transaction_date'],
        ]);
    }

    /**
     * Set the creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Transaction from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => now()->toDateString(),
        ]);
    }
}
