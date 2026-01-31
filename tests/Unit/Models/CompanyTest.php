<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);
    }

    public function test_it_creates_company(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
        ]);
    }

    public function test_it_belongs_to_default_currency(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $this->assertInstanceOf(Currency::class, $company->defaultCurrency);
        $this->assertEquals('USD', $company->defaultCurrency->code);
    }
}
