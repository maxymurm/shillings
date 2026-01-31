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

class ReportTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        $this->currency = Currency::where('code', 'USD')->first();

        $this->user = User::factory()->create();
        $this->user->assignRole('owner');

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);
        $this->company->users()->attach($this->user->id);

        // Create accounts and transactions for reports
        $this->createSampleData();
    }

    protected function createSampleData(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $expenseType = AccountType::where('name', 'EXPENSE')->first();

        $cash = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $revenue = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
        ]);

        $expense = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'code' => '5100',
            'name' => 'Operating Expenses',
        ]);

        // Income transaction
        $txn1 = Transaction::create([
            'company_id' => $this->company->id,
            'date' => now()->subDays(5),
            'description' => 'Sales income',
            'is_posted' => true,
        ]);
        $txn1->splits()->createMany([
            [
                'account_id' => $cash->id,
                'action' => 'DEBIT',
                'amount_num' => 100000,
                'amount_denom' => 100,
                'value_num' => 100000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $revenue->id,
                'action' => 'CREDIT',
                'amount_num' => 100000,
                'amount_denom' => 100,
                'value_num' => 100000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Expense transaction
        $txn2 = Transaction::create([
            'company_id' => $this->company->id,
            'date' => now()->subDays(3),
            'description' => 'Office rent',
            'is_posted' => true,
        ]);
        $txn2->splits()->createMany([
            [
                'account_id' => $expense->id,
                'action' => 'DEBIT',
                'amount_num' => 30000,
                'amount_denom' => 100,
                'value_num' => 30000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $cash->id,
                'action' => 'CREDIT',
                'amount_num' => 30000,
                'amount_denom' => 100,
                'value_num' => 30000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);
    }

    public function test_user_can_view_reports_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/reports')
                ->waitForText('Reports')
                ->assertSee('Trial Balance')
                ->assertSee('Balance Sheet')
                ->assertSee('Income Statement');
        });
    }

    public function test_user_can_view_trial_balance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/reports')
                ->waitForText('Trial Balance')
                ->click('@trial-balance-button')
                ->waitForText('Debit')
                ->assertSee('Credit')
                ->assertSee('Cash');
        });
    }

    public function test_user_can_view_balance_sheet(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/reports')
                ->waitForText('Balance Sheet')
                ->click('@balance-sheet-button')
                ->waitForText('Assets')
                ->assertSee('Cash');
        });
    }

    public function test_user_can_view_income_statement(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/reports')
                ->waitForText('Income Statement')
                ->click('@income-statement-button')
                ->waitForText('Revenue')
                ->assertSee('Expenses')
                ->assertSee('Sales Revenue')
                ->assertSee('Operating Expenses');
        });
    }

    public function test_dashboard_shows_charts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin')
                ->waitFor('.fi-wi-chart')
                ->assertPresent('.fi-wi-chart');
        });
    }
}
