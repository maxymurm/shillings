<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\ScheduledTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Account $expenseAccount;
    protected Account $bankAccount;
    protected Currency $currency;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
        ]);
        
        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);
        
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'company_id' => $this->company->id,
        ]);
        
        $assetType = AccountType::factory()->asset()->create();
        $expenseType = AccountType::factory()->expense()->create();
        
        $this->bankAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Bank Account',
        ]);
        
        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Rent Expense',
        ]);
        
        $this->token = $this->user->createToken('test-token', [
            'scheduled:read',
            'scheduled:create',
            'scheduled:update',
            'scheduled:delete',
        ])->plainTextToken;
    }

    public function test_it_lists_scheduled_transactions(): void
    {
        ScheduledTransaction::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/scheduled-transactions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_status(): void
    {
        ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        
        ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/scheduled-transactions?is_active=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_creates_scheduled_transaction(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/scheduled-transactions', [
            'name' => 'Monthly Rent',
            'description' => 'Office rent payment',
            'frequency' => 'monthly',
            'frequency_interval' => 1,
            'start_date' => now()->toDateString(),
            'day_of_month' => 1,
            'auto_create' => true,
            'auto_post' => true,
            'splits' => [
                [
                    'account_id' => $this->expenseAccount->id,
                    'amount' => 50000.00,
                    'action' => 'DEBIT',
                ],
                [
                    'account_id' => $this->bankAccount->id,
                    'amount' => 50000.00,
                    'action' => 'CREDIT',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Monthly Rent')
            ->assertJsonPath('data.frequency', 'monthly');

        $this->assertDatabaseHas('scheduled_transactions', [
            'name' => 'Monthly Rent',
            'frequency' => 'monthly',
        ]);
    }

    public function test_it_shows_scheduled_transaction(): void
    {
        $scheduled = ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Quarterly Tax',
        ]);

        $response = $this->withToken($this->token)->getJson("/api/scheduled-transactions/{$scheduled->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Quarterly Tax');
    }

    public function test_it_updates_scheduled_transaction(): void
    {
        $scheduled = ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
            'frequency' => 'monthly',
        ]);

        $response = $this->withToken($this->token)->putJson("/api/scheduled-transactions/{$scheduled->id}", [
            'name' => 'Updated Name',
            'frequency' => 'weekly',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.frequency', 'weekly');
    }

    public function test_it_pauses_scheduled_transaction(): void
    {
        $scheduled = ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'is_paused' => false,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/scheduled-transactions/{$scheduled->id}/pause");

        $response->assertOk();
        
        $this->assertTrue($scheduled->fresh()->is_paused);
    }

    public function test_it_resumes_scheduled_transaction(): void
    {
        $scheduled = ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'is_paused' => true,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/scheduled-transactions/{$scheduled->id}/resume");

        $response->assertOk();
        
        $this->assertFalse($scheduled->fresh()->is_paused);
    }

    public function test_it_deletes_scheduled_transaction(): void
    {
        $scheduled = ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/scheduled-transactions/{$scheduled->id}");

        $response->assertOk();
        
        $this->assertSoftDeleted('scheduled_transactions', ['id' => $scheduled->id]);
    }

    public function test_it_gets_due_transactions(): void
    {
        // Create a due transaction
        ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'next_occurrence' => now()->subDay(),
            'is_active' => true,
        ]);
        
        // Create a future transaction
        ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'next_occurrence' => now()->addDays(7),
            'is_active' => true,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/scheduled-transactions/due');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_processes_due_transactions(): void
    {
        ScheduledTransaction::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'next_occurrence' => now()->subDay(),
            'is_active' => true,
            'auto_create' => true,
            'splits_data' => [
                [
                    'account_id' => $this->expenseAccount->id,
                    'amount' => 1000.00,
                    'action' => 'DEBIT',
                ],
                [
                    'account_id' => $this->bankAccount->id,
                    'amount' => 1000.00,
                    'action' => 'CREDIT',
                ],
            ],
        ]);

        $response = $this->withToken($this->token)->postJson('/api/scheduled-transactions/process-all-due');

        $response->assertOk()
            ->assertJsonPath('data.processed', 1)
            ->assertJsonPath('data.created', 1);
    }

    public function test_weekly_frequency_calculates_next_occurrence(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/scheduled-transactions', [
            'name' => 'Weekly Meeting Expense',
            'frequency' => 'weekly',
            'frequency_interval' => 1,
            'start_date' => now()->toDateString(),
            'day_of_week' => 1, // Monday
            'auto_create' => false,
            'splits' => [
                [
                    'account_id' => $this->expenseAccount->id,
                    'amount' => 100.00,
                    'action' => 'DEBIT',
                ],
                [
                    'account_id' => $this->bankAccount->id,
                    'amount' => 100.00,
                    'action' => 'CREDIT',
                ],
            ],
        ]);

        $response->assertCreated();
        
        $scheduled = ScheduledTransaction::where('name', 'Weekly Meeting Expense')->first();
        $this->assertNotNull($scheduled->next_occurrence);
    }

    public function test_validation_requires_name(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/scheduled-transactions', [
            'frequency' => 'monthly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validation_requires_frequency(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/scheduled-transactions', [
            'name' => 'Test',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }
}
