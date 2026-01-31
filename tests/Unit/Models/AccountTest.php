<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected AccountType $accountType;
    protected Currency $currency;

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
    }

    public function test_it_creates_account(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);
    }

    public function test_code_is_unique_per_company(): void
    {
        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Petty Cash',
            'code' => '1000',
        ]);
    }

    public function test_it_belongs_to_company(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->assertInstanceOf(Company::class, $account->company);
    }

    public function test_it_belongs_to_account_type(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->assertInstanceOf(AccountType::class, $account->accountType);
    }

    public function test_it_belongs_to_currency(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->assertInstanceOf(Currency::class, $account->currency);
    }

    public function test_it_supports_parent_child_hierarchy(): void
    {
        $parent = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Current Assets',
            'code' => '1000',
        ]);

        $child = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'parent_id' => $parent->id,
            'name' => 'Cash',
            'code' => '1001',
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertInstanceOf(Account::class, $child->parent);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_it_can_be_placeholder(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Current Assets',
            'code' => '1000',
            'is_placeholder' => true,
        ]);

        $this->assertTrue($account->is_placeholder);
    }

    public function test_it_can_be_hidden(): void
    {
        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $this->accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Old Account',
            'code' => '9999',
            'is_hidden' => true,
        ]);

        $this->assertTrue($account->is_hidden);
    }
}
