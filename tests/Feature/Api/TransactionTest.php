<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
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

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_it_lists_transactions(): void
    {
        Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction 1',
        ]);

        Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-16',
            'description' => 'Test Transaction 2',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'transaction_date', 'description'],
                ],
            ]);
    }

    public function test_it_creates_transaction(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/transactions', [
                'company_id' => $this->company->id,
                'transaction_date' => '2026-01-15',
                'description' => 'Office supplies purchase',
                'splits' => [
                    [
                        'account_id' => $this->expenseAccount->id,
                        'amount' => 100.00,
                        'action' => 'DEBIT',
                    ],
                    [
                        'account_id' => $this->cashAccount->id,
                        'amount' => 100.00,
                        'action' => 'CREDIT',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.description', 'Office supplies purchase');

        $this->assertDatabaseHas('transactions', [
            'description' => 'Office supplies purchase',
        ]);
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
            ->assertJsonPath('data.description', 'Test Transaction');
    }

    public function test_it_updates_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Original description',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/transactions/' . $transaction->id, [
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.description', 'Updated description');
    }

    public function test_it_deletes_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'To be deleted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(204);
    }

    public function test_it_posts_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
            'is_posted' => false,
        ]);

        // Create balanced splits
        $transaction->splits()->create([
            'account_id' => $this->expenseAccount->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $transaction->splits()->create([
            'account_id' => $this->cashAccount->id,
            'amount_num' => -10000,
            'amount_denom' => 100,
            'value_num' => -10000,
            'value_denom' => 100,
            'action' => 'CREDIT',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/transactions/' . $transaction->id . '/post');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_posted', true);
    }
}
