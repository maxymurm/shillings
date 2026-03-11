<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\Company;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'account_id' => Account::factory(),
            'file_name' => $this->faker->word() . '.csv',
            'file_path' => null,
            'type' => $this->faker->randomElement(['csv', 'ofx', 'qfx']),
            'status' => 'pending',
            'total_rows' => $this->faker->numberBetween(10, 100),
            'matched_count' => 0,
            'created_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'mapping' => [
                'date' => 'Date',
                'amount' => 'Amount',
                'description' => 'Description',
            ],
            'imported_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'matched_count' => $this->faker->numberBetween(0, $attributes['total_rows']),
            'created_count' => function (array $attributes) {
                return $attributes['total_rows'] - $attributes['matched_count'];
            },
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_count' => $this->faker->numberBetween(1, 10),
            'errors' => [
                [
                    'message' => 'Invalid date format',
                    'row' => 5,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    public function csv(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'csv',
            'file_name' => 'transactions.csv',
        ]);
    }

    public function ofx(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ofx',
            'file_name' => 'transactions.ofx',
        ]);
    }
}
