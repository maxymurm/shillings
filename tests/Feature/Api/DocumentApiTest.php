<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Document;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Contact $customer;
    protected Account $incomeAccount;
    protected Account $bankAccount;
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
        
        $this->customer = Contact::factory()->customer()->create(['company_id' => $this->company->id]);
        
        // Create an income account for document items
        $incomeType = AccountType::factory()->income()->create();
        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $currency->id,
            'name' => 'Sales Income',
        ]);
        
        // Create a bank account for payments
        $assetType = AccountType::factory()->asset()->create();
        $this->bankAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $currency->id,
            'name' => 'Bank Account',
        ]);
        
        $this->token = $this->user->createToken('test-token', [
            'documents:read',
            'documents:create',
            'documents:update',
            'documents:delete',
        ])->plainTextToken;
    }

    public function test_it_lists_documents(): void
    {
        Document::factory()->count(3)->invoice()->create(['company_id' => $this->company->id]);
        Document::factory()->count(2)->bill()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/documents');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_it_filters_by_type(): void
    {
        Document::factory()->count(3)->invoice()->create(['company_id' => $this->company->id]);
        Document::factory()->count(2)->bill()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/documents?type=invoice');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_status(): void
    {
        Document::factory()->count(2)->invoice()->draft()->create(['company_id' => $this->company->id]);
        Document::factory()->count(1)->invoice()->sent()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/documents?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_creates_invoice(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/documents', [
            'type' => 'invoice',
            'document_number' => 'INV-2026-000001',
            'contact_id' => $this->customer->id,
            'issued_at' => '2026-02-01',
            'due_at' => '2026-03-01',
            'currency_code' => 'KES',
            'items' => [
                [
                    'account_id' => $this->incomeAccount->id,
                    'description' => 'Consulting Services',
                    'quantity' => 10,
                    'price' => 5000.00,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'invoice')
            ->assertJsonPath('data.document_number', 'INV-2026-000001');

        $this->assertDatabaseHas('documents', [
            'document_number' => 'INV-2026-000001',
            'type' => 'invoice',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_creates_bill(): void
    {
        $vendor = Contact::factory()->vendor()->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->postJson('/api/documents', [
            'type' => 'bill',
            'document_number' => 'BILL-2026-000001',
            'contact_id' => $vendor->id,
            'issued_at' => '2026-02-01',
            'due_at' => '2026-03-01',
            'currency_code' => 'KES',
            'items' => [
                [
                    'account_id' => $this->incomeAccount->id,
                    'description' => 'Office Supplies',
                    'quantity' => 1,
                    'price' => 1000.00,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'bill');
    }

    public function test_it_creates_quote(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/documents', [
            'type' => 'quote',
            'document_number' => 'QTE-2026-000001',
            'contact_id' => $this->customer->id,
            'issued_at' => '2026-02-01',
            'due_at' => '2026-02-15',
            'currency_code' => 'KES',
            'items' => [
                [
                    'account_id' => $this->incomeAccount->id,
                    'description' => 'Consulting Services',
                    'quantity' => 10,
                    'price' => 5000.00,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'quote');
    }

    public function test_it_shows_document(): void
    {
        $document = Document::factory()->invoice()->create([
            'company_id' => $this->company->id,
            'document_number' => 'INV-TEST-001',
        ]);

        $response = $this->withToken($this->token)->getJson("/api/documents/{$document->id}");

        $response->assertOk()
            ->assertJsonPath('data.document_number', 'INV-TEST-001');
    }

    public function test_it_updates_draft_document(): void
    {
        $document = Document::factory()->invoice()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->putJson("/api/documents/{$document->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_it_cannot_update_sent_document(): void
    {
        $document = Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->putJson("/api/documents/{$document->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Document cannot be edited');
    }

    public function test_it_deletes_draft_document(): void
    {
        $document = Document::factory()->invoice()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/documents/{$document->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Document deleted successfully');

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_it_sends_document(): void
    {
        $document = Document::factory()->invoice()->draft()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$document->id}/send");

        $response->assertOk()
            ->assertJsonPath('data.status', 'sent');

        $this->assertNotNull($document->fresh()->sent_at);
    }

    public function test_it_records_payment(): void
    {
        $document = Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'total_num' => 100000, // 1000.00
            'total_denom' => 100,
            'amount_paid_num' => 0,
            'amount_paid_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$document->id}/payment", [
            'amount' => 500.00,
            'payment_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-02-15',
            'payment_method' => 'bank_transfer',
            'reference' => 'CHK-123456',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.document.status', 'partial');

        $this->assertEquals(50000, $document->fresh()->amount_paid_num);
    }

    public function test_full_payment_marks_as_paid(): void
    {
        $document = Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'total_num' => 100000,
            'total_denom' => 100,
            'amount_paid_num' => 0,
            'amount_paid_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$document->id}/payment", [
            'amount' => 1000.00,
            'payment_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-02-15',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.document.status', 'paid');
    }

    public function test_it_cancels_document(): void
    {
        $document = Document::factory()->invoice()->draft()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$document->id}/cancel", [
            'reason' => 'Customer requested cancellation',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_it_converts_quote_to_invoice(): void
    {
        $quote = Document::factory()->quote()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
            'document_number' => 'QTE-2026-000001',
            'total_num' => 100000,
            'total_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$quote->id}/convert-to-invoice");

        $response->assertCreated()
            ->assertJsonPath('data.type', 'invoice');

        $invoice = Document::find($response->json('data.id'));
        $this->assertEquals($quote->id, $invoice->parent_id);
    }

    public function test_it_duplicates_document(): void
    {
        $original = Document::factory()->invoice()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
            'notes' => 'Original notes',
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$original->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $duplicate = Document::find($response->json('data.id'));
        $this->assertNotEquals($original->document_number, $duplicate->document_number);
    }

    public function test_it_creates_credit_note(): void
    {
        $invoice = Document::factory()->invoice()->paid()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->customer->id,
            'total_num' => 100000,
            'total_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/documents/{$invoice->id}/credit-note", [
            'reason' => 'Returned goods',
            'items' => [
                [
                    'account_id' => $this->incomeAccount->id,
                    'description' => 'Returned item',
                    'quantity' => 1,
                    'price' => 500.00,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'credit_note');

        $creditNote = Document::find($response->json('data.id'));
        $this->assertEquals($invoice->id, $creditNote->parent_id);
    }

    public function test_it_lists_overdue_documents(): void
    {
        // Create overdue invoice
        Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'due_at' => now()->subDays(10),
        ]);

        // Create not-yet-due invoice
        Document::factory()->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'due_at' => now()->addDays(10),
        ]);

        $response = $this->withToken($this->token)->getJson('/api/documents/overdue');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_gets_summary(): void
    {
        Document::factory()->count(3)->invoice()->sent()->create([
            'company_id' => $this->company->id,
            'total_num' => 100000,
            'total_denom' => 100,
        ]);

        Document::factory()->count(2)->bill()->sent()->create([
            'company_id' => $this->company->id,
            'total_num' => 50000,
            'total_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/documents/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'invoices' => ['count', 'total_num', 'total_denom', 'paid_num', 'paid_denom'],
                    'bills' => ['count', 'total_num', 'total_denom', 'paid_num', 'paid_denom'],
                    'quotes' => ['count', 'total_num', 'total_denom'],
                ],
            ]);
    }

    public function test_validation_requires_contact(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/documents', [
            'type' => 'invoice',
            'document_number' => 'INV-001',
            'issued_at' => '2026-02-01',
            'due_at' => '2026-03-01',
            // Missing contact_id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact_id']);
    }
}
