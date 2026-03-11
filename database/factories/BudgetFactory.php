<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $year = (int) date('Y');
        
        return [
            'company_id' => Company::factory(),
            'name' => $year . ' Operating Budget',
            'fiscal_year' => $year,
            'num_periods' => 12,
            'recurrence' => 'monthly',
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'is_active' => true,
        ];
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'num_periods' => 4,
            'recurrence' => 'quarterly',
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'num_periods' => 1,
            'recurrence' => 'annual',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn () => [
            'name' => $year . ' Operating Budget',
            'fiscal_year' => $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
        ]);
    }
}
