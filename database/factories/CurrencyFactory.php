<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->currencyCode()),
            'name' => $this->faker->words(2, true),
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', 'KSh', 'R']),
            'decimal_places' => 2,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create USD currency.
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);
    }

    /**
     * Create EUR currency.
     */
    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'decimal_places' => 2,
        ]);
    }

    /**
     * Create KES currency.
     */
    public function kes(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
        ]);
    }
}
