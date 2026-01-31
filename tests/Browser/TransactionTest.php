<?php

namespace Tests\Browser;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TransactionTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected Currency $currency;
    protected Account $cashAccount;
    protected Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        $this->currency = Currency::where('code', 'USD')->first();
        $assetType = AccountType::where('name', 'ASSET')->first();
        $expenseType = AccountType::where('name', 'EXPENSE')->first();

        $this->user = User::factory()->create();
        $this->user->assignRole('owner');

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);
        $this->company->users()->attach($this->user->id);

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'code' => '5100',
            'name' => 'Office Supplies',
        ]);
    }

    public function test_user_can_view_transactions_list(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'date' => now(),
            'description' => 'Office supplies purchase',
            'is_posted' => true,
        ]);

        $transaction->splits()->createMany([
            [
                'account_id' => $this->expenseAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 5000,
                'amount_denom' => 100,
                'value_num' => 5000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->cashAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 5000,
                'amount_denom' => 100,
                'value_num' => 5000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/transactions')
                ->waitForText('Office supplies purchase')
                ->assertSee('Office supplies purchase');
        });
    }

    public function test_user_can_create_transaction(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/transactions/create')
                ->waitFor('input[name="description"]')
                ->type('description', 'Test Transaction')
                ->type('date', now()->format('Y-m-d'))
                // Add splits through repeater
                ->press('Add Split')
                ->waitFor('[wire\\:key*="splits"]')
                ->select('splits.0.account_id', $this->expenseAccount->id)
                ->type('splits.0.amount', '100.00')
                ->select('splits.0.action', 'DEBIT')
                ->press('Add Split')
                ->select('splits.1.account_id', $this->cashAccount->id)
                ->type('splits.1.amount', '100.00')
                ->select('splits.1.action', 'CREDIT')
                ->press('Create')
                ->waitForText('Created')
                ->assertSee('Test Transaction');
        });
    }

    public function test_user_can_view_transaction_details(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'date' => now(),
            'num' => 'TXN-001',
            'description' => 'Detailed transaction',
            'notes' => 'Some notes here',
            'is_posted' => true,
        ]);

        $transaction->splits()->createMany([
            [
                'account_id' => $this->expenseAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->cashAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($transaction) {
            $browser->loginAs($this->user)
                ->visit("/admin/transactions/{$transaction->id}")
                ->waitForText('Detailed transaction')
                ->assertSee('TXN-001')
                ->assertSee('Some notes here')
                ->assertSee('Office Supplies')
                ->assertSee('Cash');
        });
    }

    public function test_transaction_requires_balanced_splits(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/transactions/create')
                ->waitFor('input[name="description"]')
                ->type('description', 'Unbalanced Transaction')
                ->type('date', now()->format('Y-m-d'))
                ->press('Add Split')
                ->waitFor('[wire\\:key*="splits"]')
                ->select('splits.0.account_id', $this->expenseAccount->id)
                ->type('splits.0.amount', '100.00')
                ->select('splits.0.action', 'DEBIT')
                // Only one split - should fail validation
                ->press('Create')
                ->waitFor('.fi-fo-field-wrp-error-message')
                ->assertSee('balanced');
        });
    }
}
