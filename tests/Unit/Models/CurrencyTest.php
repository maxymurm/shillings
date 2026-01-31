<?php

namespace Tests\Unit\Models;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_currency(): void
    {
        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);

        $this->assertDatabaseHas('currencies', [
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);
    }

    public function test_code_must_be_unique(): void
    {
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Currency::create([
            'code' => 'USD',
            'name' => 'Another Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);
    }
}
