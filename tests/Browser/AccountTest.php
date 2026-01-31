<?php

namespace Tests\Browser;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccountTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;
    protected Currency $currency;
    protected AccountType $accountType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        $this->currency = Currency::where('code', 'USD')->first();
        $this->accountType = AccountType::where('name', 'ASSET')->first();

        $this->user = User::factory()->create();
        $this->user->assignRole('owner');

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);
        $this->company->users()->attach($this->user->id);
    }

    public function test_user_can_view_accounts_list(): void
    {
        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Cash',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/accounts')
                ->waitForText('Cash')
                ->assertSee('Cash')
                ->assertSee('1000');
        });
    }

    public function test_user_can_create_account(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/accounts/create')
                ->waitFor('input[name="name"]')
                ->type('name', 'Bank Account')
                ->type('code', '1100')
                ->select('account_type_id', $this->accountType->id)
                ->select('currency_id', $this->currency->id)
                ->press('Create')
                ->waitForText('Created')
                ->assertSee('Bank Account');
        });
    }

    public function test_user_can_edit_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Cash',
        ]);

        $this->browse(function (Browser $browser) use ($account) {
            $browser->loginAs($this->user)
                ->visit("/admin/accounts/{$account->id}/edit")
                ->waitFor('input[name="name"]')
                ->clear('name')
                ->type('name', 'Petty Cash')
                ->press('Save changes')
                ->waitForText('Saved')
                ->assertSee('Petty Cash');
        });
    }

    public function test_user_can_delete_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Cash',
        ]);

        $this->browse(function (Browser $browser) use ($account) {
            $browser->loginAs($this->user)
                ->visit('/admin/accounts')
                ->waitForText('Cash')
                ->click("@delete-{$account->id}")
                ->waitFor('.fi-modal')
                ->press('Delete')
                ->waitUntilMissingText('Cash')
                ->assertDontSee('Cash');
        });
    }

    public function test_account_tree_shows_hierarchy(): void
    {
        $parent = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Assets',
            'is_placeholder' => true,
        ]);

        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'parent_id' => $parent->id,
            'code' => '1100',
            'name' => 'Current Assets',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/accounts')
                ->waitForText('Assets')
                ->assertSee('Assets')
                ->assertSee('Current Assets');
        });
    }
}
