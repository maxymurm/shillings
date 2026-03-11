<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(['customer', 'vendor', 'employee']),
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'tax_number' => 'P' . $this->faker->numerify('#########') . $this->faker->randomLetter(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'country' => 'Kenya',
            'currency_code' => 'KES',
            'notes' => $this->faker->optional()->paragraph(),
            'enabled' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn () => ['type' => 'customer']);
    }

    public function vendor(): static
    {
        return $this->state(fn () => ['type' => 'vendor']);
    }

    public function employee(): static
    {
        return $this->state(fn () => ['type' => 'employee']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}
