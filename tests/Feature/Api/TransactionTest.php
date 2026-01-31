<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Split;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Currency $currency;
    protected Account $cashAccount;
    protected Account $expenseAccount;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $assetType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $expenseType = AccountType::create([
            'name' => 'Expense',
            'normal_balance' => 'debit',
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Office Supplies',
            'code' => '5000',
        ]);

        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
        ]);

        // Create token with all abilities for testing
        $this->token = $this->user->createToken('test-token', [
            'transactions:read',
            'transactions:create',
            'transactions:update',
            'transactions:delete',
            'transactions:post',
            'accounts:read',
        ])->plainTextToken;
    }

    public function test_it_lists_transactions(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction 1',
        ]);

        Split::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->cashAccount->id,
            'amount_num' => -5000,
            'amount_denom' => 100,
            'action' => 'CREDIT',
        ]);

        Split::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->expenseAccount->id,
            'amount_num' => 5000,
            'amount_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/transactions');

        $response->assertStatus(200);
        
        // Verify transaction is in paginated response
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_shows_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(200)
            ->assertJsonPath('transaction.description', 'Test Transaction');
    }

    public function test_it_updates_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/transactions/' . $transaction->id, [
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('transaction.description', 'Updated description');
    }

    public function test_it_deletes_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/transactions/' . $transaction->id);

        // Controller returns 200 with message
        $response->assertStatus(200);
    }

    public function test_it_posts_balanced_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
            'is_posted' => false,
        ]);

        // Add balanced splits
        Split::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->cashAccount->id,
            'amount_num' => -5000,
            'amount_denom' => 100,
            'action' => 'CREDIT',
        ]);

        Split::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->expenseAccount->id,
            'amount_num' => 5000,
            'amount_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/transactions/' . $transaction->id . '/post');

        $response->assertStatus(200)
            ->assertJsonPath('transaction.is_posted', true);
    }
}
