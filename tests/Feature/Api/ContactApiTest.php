<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactApiTest extends TestCase
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
            'contacts:read',
            'contacts:create',
            'contacts:update',
            'contacts:delete',
        ])->plainTextToken;
    }

    public function test_it_lists_contacts(): void
    {
        Contact::factory()->count(3)->customer()->create(['company_id' => $this->company->id]);
        Contact::factory()->count(2)->vendor()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/contacts');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_it_filters_contacts_by_type(): void
    {
        Contact::factory()->count(3)->customer()->create(['company_id' => $this->company->id]);
        Contact::factory()->count(2)->vendor()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/contacts?type=customer');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_creates_customer(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/contacts', [
            'name' => 'Acme Corp',
            'type' => 'customer',
            'email' => 'contact@acme.com',
            'phone' => '+254700123456',
            'tax_number' => 'P051234567A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Acme Corp')
            ->assertJsonPath('data.type', 'customer');

        $this->assertDatabaseHas('contacts', [
            'name' => 'Acme Corp',
            'type' => 'customer',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_creates_vendor(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/contacts', [
            'name' => 'Supplier Inc',
            'type' => 'vendor',
            'email' => 'vendor@supplier.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'vendor');
    }

    public function test_it_shows_contact(): void
    {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
        ]);

        $response = $this->withToken($this->token)->getJson("/api/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Test Customer');
    }

    public function test_it_updates_contact(): void
    {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        $response = $this->withToken($this->token)->putJson("/api/contacts/{$contact->id}", [
            'name' => 'New Name',
            'phone' => '+254700999888',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_deletes_contact(): void
    {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Contact deleted successfully');

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_it_gets_customer_statement(): void
    {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        // Create some invoices for this customer
        Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'contact_id' => $contact->id,
            'total_num' => 100000, // 1000.00
            'total_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->getJson("/api/contacts/{$contact->id}/statement");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'contact',
                    'period',
                    'opening_balance',
                    'transactions',
                    'closing_balance',
                ],
            ]);
    }

    public function test_it_gets_customers_with_balances(): void
    {
        // Create customers
        $customer1 = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        // Create unpaid invoices
        Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'contact_id' => $customer1->id,
            'total_num' => 100000,
            'total_denom' => 100,
            'amount_paid_num' => 0,
            'amount_paid_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/contacts/customers-with-balances');

        $response->assertOk();
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/contacts');

        $response->assertStatus(401);
    }

    public function test_contact_validation(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/contacts', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_it_can_add_contact_person(): void
    {
        $contact = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->putJson("/api/contacts/{$contact->id}", [
            'persons' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@acme.com',
                    'phone' => '+254700111222',
                    'is_primary' => true,
                ],
            ],
        ]);

        $response->assertOk();
    }
}
