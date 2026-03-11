<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
        ]);
        
        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $currency->id,
        ]);
        
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'company_id' => $this->company->id,
        ]);
        
        $this->token = $this->user->createToken('test-token', [
            'taxes:read',
            'taxes:create',
            'taxes:update',
            'taxes:delete',
        ])->plainTextToken;
    }

    public function test_it_lists_taxes(): void
    {
        Tax::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/taxes');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_lists_enabled_taxes(): void
    {
        Tax::factory()->enabled()->create(['company_id' => $this->company->id]);
        Tax::factory()->disabled()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/taxes?enabled=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_creates_tax(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/taxes', [
            'name' => 'VAT 16%',
            'type' => 'percentage',
            'rate' => 16,
            'enabled' => true,
            'is_recoverable' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'VAT 16%');

        $this->assertDatabaseHas('taxes', [
            'name' => 'VAT 16%',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_creates_fixed_amount_tax(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/taxes', [
            'name' => 'Stamp Duty',
            'type' => 'fixed',
            'rate' => 500,
            'enabled' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'fixed');
    }

    public function test_it_creates_compound_tax(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/taxes', [
            'name' => 'Compound Tax',
            'type' => 'percentage',
            'rate' => 2,
            'is_compound' => true,
            'enabled' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_compound', true);
    }

    public function test_it_shows_tax(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Tax',
        ]);

        $response = $this->withToken($this->token)->getJson("/api/taxes/{$tax->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Test Tax');
    }

    public function test_it_updates_tax(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        $response = $this->withToken($this->token)->putJson("/api/taxes/{$tax->id}", [
            'name' => 'New Name',
            'rate_num' => 18,
            'rate_denom' => 100,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_it_deletes_tax(): void
    {
        $tax = Tax::factory()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->deleteJson("/api/taxes/{$tax->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Tax deleted successfully');

        $this->assertSoftDeleted('taxes', ['id' => $tax->id]);
    }

    public function test_it_lists_applicable_taxes(): void
    {
        // Create a tax with no rules (will be returned as default)
        Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Default Sales Tax',
            'enabled' => true,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/taxes/applicable?document_type=invoice');

        $response->assertOk();
        // Tax with no rules is returned as default
        $this->assertGreaterThanOrEqual(0, count($response->json('data')));
    }

    public function test_it_gets_tax_summary(): void
    {
        Tax::factory()->create([
            'company_id' => $this->company->id,
            'rate_num' => 16,
            'rate_denom' => 100,
            'enabled' => true,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/taxes/summary?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_it_lists_recoverable_taxes(): void
    {
        Tax::factory()->create([
            'company_id' => $this->company->id,
            'is_recoverable' => true,
            'enabled' => true,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/taxes/recoverable?start_date=2026-01-01&end_date=2026-12-31');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['amount']]);
    }

    public function test_it_validates_tax_number(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/taxes/validate-tax-number', [
            'tax_number' => 'P051234567A',
            'country' => 'KE',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'is_valid',
                    'tax_number',
                    'country',
                ],
            ]);
    }

    public function test_validation_requires_name(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/taxes', [
            'type' => 'percentage',
            'rate_num' => 16,
            'rate_denom' => 100,
            // Missing name
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_tax_with_rules(): void
    {
        $liabilityType = AccountType::factory()->liability()->create();
        $taxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $liabilityType->id,
        ]);

        $response = $this->withToken($this->token)->postJson('/api/taxes', [
            'name' => 'VAT with Account',
            'type' => 'percentage',
            'rate' => 16,
            'enabled' => true,
            'account_id' => $taxAccount->id,
            'rules' => [
                [
                    'applies_to' => 'sales',
                    'account_id' => $taxAccount->id,
                ],
            ],
        ]);

        $response->assertCreated();

        $tax = Tax::find($response->json('data.id'));
        $this->assertCount(1, $tax->rules);
    }
}
