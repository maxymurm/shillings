<?php

namespace Tests\Unit\Accounting;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Split;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleEntryValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Currency $currency;
    protected Account $assetAccount;
    protected Account $expenseAccount;
    protected Account $incomeAccount;
    protected Account $liabilityAccount;
    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountTypeSeeder::class);

        $this->currency = Currency::where('code', 'USD')->first();
        $assetType = AccountType::where('name', 'ASSET')->first();
        $expenseType = AccountType::where('name', 'EXPENSE')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $liabilityType = AccountType::where('name', 'LIABILITY')->first();

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $this->assetAccount = Account::create([
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

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
        ]);

        $this->liabilityAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $liabilityType->id,
            'currency_id' => $this->currency->id,
            'code' => '2100',
            'name' => 'Accounts Payable',
        ]);

        $this->transactionService = new TransactionService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debits_must_equal_credits(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Balanced transaction',
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
                'account_id' => $this->assetAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->assertTrue($this->transactionService->isBalanced($transaction));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unbalanced_transaction_fails_validation(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Unbalanced transaction',
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
                'account_id' => $this->assetAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 5000, // Different amount!
                'amount_denom' => 100,
                'value_num' => 5000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->assertFalse($this->transactionService->isBalanced($transaction));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_requires_at_least_two_splits(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Single split transaction',
            'is_posted' => true,
        ]);

        $transaction->splits()->create([
            'account_id' => $this->expenseAccount->id,
            'action' => 'DEBIT',
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'reconciled_state' => 'n',
        ]);

        $this->assertFalse($this->transactionService->isBalanced($transaction));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multi_split_transaction_must_balance(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Multi-split transaction',
            'is_posted' => true,
        ]);

        // Expense split into multiple accounts
        $transaction->splits()->createMany([
            [
                'account_id' => $this->expenseAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 3000,
                'amount_denom' => 100,
                'value_num' => 3000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->liabilityAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 7000,
                'amount_denom' => 100,
                'value_num' => 7000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->assetAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->assertTrue($this->transactionService->isBalanced($transaction));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_increases_asset_accounts(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Receive payment',
            'is_posted' => true,
        ]);

        $transaction->splits()->createMany([
            [
                'account_id' => $this->assetAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Asset account should increase (debit balance)
        $balance = $this->assetAccount->splits()
            ->selectRaw("SUM(CASE WHEN action = 'DEBIT' THEN amount_num ELSE -amount_num END) as balance")
            ->value('balance');

        $this->assertEquals(10000, $balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_increases_liability_accounts(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Incur debt',
            'is_posted' => true,
        ]);

        $transaction->splits()->createMany([
            [
                'account_id' => $this->assetAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->liabilityAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Liability account should increase (credit balance)
        $balance = $this->liabilityAccount->splits()
            ->selectRaw("SUM(CASE WHEN action = 'CREDIT' THEN amount_num ELSE -amount_num END) as balance")
            ->value('balance');

        $this->assertEquals(10000, $balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function credit_increases_income_accounts(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Sales revenue',
            'is_posted' => true,
        ]);

        $transaction->splits()->createMany([
            [
                'account_id' => $this->assetAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->incomeAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Income account should increase (credit balance)
        $balance = $this->incomeAccount->splits()
            ->selectRaw("SUM(CASE WHEN action = 'CREDIT' THEN amount_num ELSE -amount_num END) as balance")
            ->value('balance');

        $this->assertEquals(10000, $balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_increases_expense_accounts(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Pay expenses',
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
                'account_id' => $this->assetAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        // Expense account should increase (debit balance)
        $balance = $this->expenseAccount->splits()
            ->selectRaw("SUM(CASE WHEN action = 'DEBIT' THEN amount_num ELSE -amount_num END) as balance")
            ->value('balance');

        $this->assertEquals(10000, $balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_with_different_denominators_must_balance(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => now(),
            'description' => 'Different denominators',
            'is_posted' => true,
        ]);

        // $100.00 expressed as 100/1 and 10000/100 should balance
        $transaction->splits()->createMany([
            [
                'account_id' => $this->expenseAccount->id,
                'action' => 'DEBIT',
                'amount_num' => 100,
                'amount_denom' => 1,
                'value_num' => 100,
                'value_denom' => 1,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $this->assetAccount->id,
                'action' => 'CREDIT',
                'amount_num' => 10000,
                'amount_denom' => 100,
                'value_num' => 10000,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        $this->assertTrue($this->transactionService->isBalanced($transaction));
    }
}
