<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'name' => 'Test Company ' . $counter,
            'fiscal_year_start' => now()->subYear()->startOfYear(),
            'default_currency_id' => function () {
                return Currency::where('code', 'KES')->first()?->id
                    ?? Currency::factory()->kes()->create()->id;
            },
            'settings' => [
                'date_format' => 'Y-m-d',
                'timezone' => 'UTC',
            ],
        ];
    }

    /**
     * Configure default USD currency.
     */
    public function withUsdCurrency(): static
    {
        return $this->state(function (array $attributes) {
            $usd = Currency::where('code', 'USD')->first()
                ?? Currency::factory()->usd()->create();

            return [
                'default_currency_id' => $usd->id,
            ];
        });
    }

    /**
     * Configure fiscal year starting January 1.
     */
    public function calendarYear(): static
    {
        return $this->state(fn (array $attributes) => [
            'fiscal_year_start' => now()->startOfYear(),
        ]);
    }
}
