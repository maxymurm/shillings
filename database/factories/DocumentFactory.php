<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['invoice', 'bill', 'quote']);
        $prefix = match ($type) {
            'invoice' => 'INV',
            'bill' => 'BILL',
            'quote' => 'QUO',
            default => 'DOC',
        };
        
        $subtotal = $this->faker->numberBetween(10000, 1000000);
        $taxTotal = (int) ($subtotal * 0.16); // 16% VAT
        $total = $subtotal + $taxTotal;

        return [
            'company_id' => Company::factory(),
            'type' => $type,
            'document_number' => $prefix . '-' . date('Y') . '-' . $this->faker->unique()->numerify('######'),
            'contact_id' => Contact::factory(),
            'status' => 'draft',
            'issued_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'currency_code' => 'KES',
            'subtotal_num' => $subtotal,
            'subtotal_denom' => 100,
            'tax_total_num' => $taxTotal,
            'tax_total_denom' => 100,
            'discount_num' => 0,
            'discount_denom' => 100,
            'total_num' => $total,
            'total_denom' => 100,
            'amount_paid_num' => 0,
            'amount_paid_denom' => 100,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function invoice(): static
    {
        return $this->state(fn () => [
            'type' => 'invoice',
            'document_number' => 'INV-' . date('Y') . '-' . $this->faker->unique()->numerify('######'),
        ]);
    }

    public function bill(): static
    {
        return $this->state(fn () => [
            'type' => 'bill',
            'document_number' => 'BILL-' . date('Y') . '-' . $this->faker->unique()->numerify('######'),
        ]);
    }

    public function quote(): static
    {
        return $this->state(fn () => [
            'type' => 'quote',
            'document_number' => 'QUO-' . date('Y') . '-' . $this->faker->unique()->numerify('######'),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'amount_paid_num' => $attributes['total_num'],
                'amount_paid_denom' => $attributes['total_denom'],
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'overdue',
            'due_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'sent_at' => null,
        ]);
    }

    public function creditNote(): static
    {
        return $this->state(fn () => [
            'type' => 'credit_note',
            'document_number' => 'CN-' . date('Y') . '-' . $this->faker->unique()->numerify('######'),
        ]);
    }

    public function withTotal(int $totalNum, int $totalDenom = 100): static
    {
        return $this->state(fn () => [
            'total_num' => $totalNum,
            'total_denom' => $totalDenom,
            'subtotal_num' => (int)($totalNum / 1.16),
            'subtotal_denom' => $totalDenom,
            'tax_total_num' => $totalNum - (int)($totalNum / 1.16),
            'tax_total_denom' => $totalDenom,
        ]);
    }
}
