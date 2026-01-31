<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Currency $currency;
    protected AccountType $accountType;
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

        $this->accountType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
        ]);

        // Create token with all abilities for testing
        $this->token = $this->user->createToken('test-token', [
            'accounts:read',
            'accounts:create',
            'accounts:update',
            'accounts:delete',
            'reports:read',
        ])->plainTextToken;
    }

    public function test_it_lists_accounts(): void
    {
        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Bank',
            'code' => '1010',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/accounts');

        $response->assertStatus(200);
        
        // Check that accounts are in the response data
        $this->assertCount(2, $response->json('data'));
    }

    public function test_it_creates_account(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/accounts', [
                'account_type_id' => $this->accountType->id,
                'currency_id' => $this->currency->id,
                'name' => 'New Cash Account',
                'code' => '1100',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('account.name', 'New Cash Account');

        $this->assertDatabaseHas('accounts', [
            'name' => 'New Cash Account',
            'code' => '1100',
        ]);
    }

    public function test_it_shows_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/accounts/' . $account->id);

        $response->assertStatus(200)
            ->assertJsonPath('account.name', 'Cash');
    }

    public function test_it_updates_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/accounts/' . $account->id, [
                'name' => 'Updated Cash Account',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('account.name', 'Updated Cash Account');
    }

    public function test_it_deletes_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/accounts/' . $account->id);

        // Controller returns 200 with message, not 204
        $response->assertStatus(200);
        
        // Check account was soft deleted or deleted
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }
}
