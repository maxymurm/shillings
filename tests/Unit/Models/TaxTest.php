<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use App\Models\TaxRule;
use App\Models\Company;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tax(): void
    {
        $tax = Tax::factory()->create([
            'name' => 'VAT 16%',
            'rate_num' => 1600,
            'rate_denom' => 10000,
        ]);

        $this->assertDatabaseHas('taxes', [
            'name' => 'VAT 16%',
            'rate_num' => 1600,
            'rate_denom' => 10000,
        ]);
    }

    public function test_it_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $tax = Tax::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($tax->company->is($company));
    }

    public function test_it_calculates_tax(): void
    {
        $tax = Tax::factory()->vat()->create(); // 16% VAT
        
        $amount = Money::fromDecimal(1000.00, 'KES');
        $taxAmount = $tax->calculate($amount);
        
        $this->assertEquals(160.00, $taxAmount->toDecimal());
    }

    public function test_it_calculates_tax_inclusive(): void
    {
        $tax = Tax::factory()->vat()->create(); // 16% VAT
        
        $total = Money::fromDecimal(1160.00, 'KES');
        $taxAmount = $tax->calculateInclusive($total);
        
        $this->assertEquals(160.00, $taxAmount->toDecimal());
    }

    public function test_it_has_rules(): void
    {
        $tax = Tax::factory()->create();
        
        TaxRule::create([
            'tax_id' => $tax->id,
            'applies_to' => 'sales',
            'priority' => 0,
        ]);

        $this->assertCount(1, $tax->fresh()->rules);
    }

    public function test_enabled_scope(): void
    {
        Tax::factory()->create(['enabled' => true]);
        Tax::factory()->create(['enabled' => false]);

        $this->assertCount(1, Tax::enabled()->get());
    }

    public function test_recoverable_scope(): void
    {
        Tax::factory()->create(['is_recoverable' => true]);
        Tax::factory()->create(['is_recoverable' => false]);

        $this->assertCount(1, Tax::recoverable()->get());
    }

    public function test_get_rate_percent(): void
    {
        $tax = Tax::factory()->create([
            'rate_num' => 1600,
            'rate_denom' => 10000,
        ]);

        $this->assertEquals(16.00, $tax->rate_percent);
    }

    public function test_compound_tax(): void
    {
        $tax = Tax::factory()->create([
            'rate_num' => 500,
            'rate_denom' => 10000, // 5%
            'is_compound' => true,
        ]);

        $this->assertTrue($tax->is_compound);
    }
}
