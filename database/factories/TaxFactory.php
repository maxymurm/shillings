<?php

namespace Database\Factories;

use App\Models\Tax;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        $rate = $this->faker->randomElement([5, 10, 12, 14, 16, 18, 20]);
        
        return [
            'company_id' => Company::factory(),
            'name' => "VAT {$rate}%",
            'rate_num' => $rate * 100,
            'rate_denom' => 10000,
            'type' => 'percentage',
            'is_compound' => false,
            'is_recoverable' => true,
            'account_id' => null,
            'enabled' => true,
        ];
    }

    public function vat(): static
    {
        return $this->state(fn () => [
            'name' => 'VAT 16%',
            'rate_num' => 1600,
            'rate_denom' => 10000,
            'is_recoverable' => true,
        ]);
    }

    public function withholdingTax(): static
    {
        return $this->state(fn () => [
            'name' => 'WHT 5%',
            'rate_num' => 500,
            'rate_denom' => 10000,
            'is_recoverable' => false,
        ]);
    }

    public function compound(): static
    {
        return $this->state(fn () => [
            'type' => 'percentage',
            'is_compound' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }

    public function enabled(): static
    {
        return $this->state(fn () => ['enabled' => true]);
    }

    public function recoverable(): static
    {
        return $this->state(fn () => ['is_recoverable' => true]);
    }

    public function nonRecoverable(): static
    {
        return $this->state(fn () => ['is_recoverable' => false]);
    }

    public function fixed(): static
    {
        return $this->state(fn () => ['type' => 'fixed']);
    }
}
