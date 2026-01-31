<?php

namespace Tests\Unit\Accounting;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Currency $currency;
    protected Account $parentAccount;
    protected Account $childAccount1;
    protected Account $childAccount2;
    protected AccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountTypeSeeder::class);

        $this->currency = Currency::where('code', 'USD')->first();
        $assetType = AccountType::where('name', 'ASSET')->first();

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $this->parentAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Assets',
            'is_placeholder' => true,
        ]);

        $this->childAccount1 = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'parent_id' => $this->parentAccount->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $this->childAccount2 = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'parent_id' => $this->parentAccount->id,
            'code' => '1200',
            'name' => 'Bank',
        ]);

        $this->accountService = new AccountService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function account_balance_is_zero_without_transactions(): void
    {
        $balance = $this->accountService->getBalance($this->childAccount1);

        $this->assertEquals(0, $balance['decimal']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function account_balance_reflects_transactions(): void
    {
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        $txn = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Receive payment',
            'is_posted' => true,
        ]);

        $txn->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $balance = $this->accountService->getBalance($this->childAccount1);

        $this->assertEquals(100.00, $balance['decimal']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parent_balance_includes_children(): void
    {
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        // Add to child 1
        $txn1 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Cash deposit',
            'is_posted' => true,
        ]);
        $txn1->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Add to child 2
        $txn2 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Bank deposit',
            'is_posted' => true,
        ]);
        $txn2->splits()->createMany([
            [
                'account_id' => $this->childAccount2->id,
                'action' => 'DEBIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $balance = $this->accountService->getChildrenBalances($this->parentAccount);

        $this->assertEquals(300.00, $balance['decimal']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function balance_as_of_date_filters_transactions(): void
    {
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        // Past transaction
        $txn1 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now()->subDays(10),
            'description' => 'Past payment',
            'is_posted' => true,
        ]);
        $txn1->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Future transaction
        $txn2 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now()->addDays(10),
            'description' => 'Future payment',
            'is_posted' => true,
        ]);
        $txn2->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $balanceAsOfToday = $this->accountService->getBalance($this->childAccount1, now());
        $balanceTotal = $this->accountService->getBalance($this->childAccount1);

        // Both should return only the past transaction (100.00) since default date is now()
        $this->assertEquals(100.00, $balanceAsOfToday['decimal']);
        // Future transactions are excluded when using current date (default)
        $this->assertEquals(100.00, $balanceTotal['decimal']);
        
        // To get balance including future transactions, pass a future date
        $balanceWithFuture = $this->accountService->getBalance($this->childAccount1, now()->addDays(30));
        $this->assertEquals(300.00, $balanceWithFuture['decimal']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unposted_transactions_excluded_from_balance(): void
    {
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        // Posted transaction
        $txn1 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Posted payment',
            'is_posted' => true,
        ]);
        $txn1->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Unposted transaction
        $txn2 = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Draft payment',
            'is_posted' => false,
        ]);
        $txn2->splits()->createMany([
            [
                'account_id' => $this->childAccount1->id,
                'action' => 'DEBIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 20000,
                'amount_denom' => 100,
                'value_num' => 20000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $balance = $this->accountService->getBalance($this->childAccount1);

        // Only posted transaction should be included
        $this->assertEquals(100.00, $balance['decimal']);
    }
}
