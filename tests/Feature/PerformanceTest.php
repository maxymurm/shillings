<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Benchmark;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Currency $currency;
    protected AccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountTypeSeeder::class);

        $this->currency = Currency::where('code', 'USD')->first();

        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);

        $this->accountService = new AccountService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function balance_calculation_performs_within_acceptable_time(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();

        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        // Create 100 transactions with 2 splits each
        for ($i = 0; $i < 100; $i++) {
            $txn = Transaction::create([
                'company_id' => $this->company->id,
                'transaction_date' => now()->subDays($i),
                'description' => "Transaction $i",
                'is_posted' => true,
            ]);

            $txn->splits()->createMany([
                [
                    'account_id' => $account->id,
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
        }

        $duration = Benchmark::measure(function () use ($account) {
            $this->accountService->getBalance($account);
        });

        // Balance calculation should complete within 100ms
        $this->assertLessThan(100, $duration, 'Balance calculation took too long');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function account_tree_query_performs_within_acceptable_time(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();

        // Create a tree of 50 accounts with 3 levels
        $root = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Assets',
            'is_placeholder' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $parent = Account::create([
                'company_id' => $this->company->id,
                'account_type_id' => $assetType->id,
                'currency_id' => $this->currency->id,
                'parent_id' => $root->id,
                'code' => "10{$i}0",
                'name' => "Category $i",
                'is_placeholder' => true,
            ]);

            for ($j = 1; $j <= 4; $j++) {
                Account::create([
                    'company_id' => $this->company->id,
                    'account_type_id' => $assetType->id,
                    'currency_id' => $this->currency->id,
                    'parent_id' => $parent->id,
                    'code' => "10{$i}{$j}",
                    'name' => "Account {$i}.{$j}",
                ]);
            }
        }

        $duration = Benchmark::measure(function () {
            Account::where('company_id', $this->company->id)
                ->with('children', 'parent', 'accountType')
                ->whereNull('parent_id')
                ->get();
        });

        // Tree query should complete within 50ms
        $this->assertLessThan(50, $duration, 'Account tree query took too long');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_listing_with_splits_performs_well(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();
        $expenseType = AccountType::where('name', 'EXPENSE')->first();

        $account1 = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $account2 = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'code' => '5100',
            'name' => 'Expenses',
        ]);

        // Create 50 transactions
        for ($i = 0; $i < 50; $i++) {
            $txn = Transaction::create([
                'company_id' => $this->company->id,
                'transaction_date' => now()->subDays($i),
                'description' => "Transaction $i",
                'is_posted' => true,
            ]);

            $txn->splits()->createMany([
                [
                    'account_id' => $account2->id,
                    'action' => 'DEBIT',
                    'amount_num' => rand(1000, 10000),
                    'amount_denom' => 100,
                    'value_num' => rand(1000, 10000),
                    'value_denom' => 100,
                    'reconciled_state' => 'n',
                ],
                [
                    'account_id' => $account1->id,
                    'action' => 'CREDIT',
                    'amount_num' => rand(1000, 10000),
                    'amount_denom' => 100,
                    'value_num' => rand(1000, 10000),
                    'value_denom' => 100,
                    'reconciled_state' => 'n',
                ],
            ]);
        }

        $duration = Benchmark::measure(function () {
            Transaction::where('company_id', $this->company->id)
                ->with(['splits.account'])
                ->orderBy('transaction_date', 'desc')
                ->paginate(15);
        });

        // Paginated transaction query should complete within 50ms
        $this->assertLessThan(50, $duration, 'Transaction listing took too long');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function database_has_necessary_indexes(): void
    {
        // Check that key indexes exist for performance
        $this->assertTrue(
            $this->hasIndex('accounts', ['company_id']),
            'accounts table should have company_id index'
        );

        $this->assertTrue(
            $this->hasIndex('transactions', ['company_id']),
            'transactions table should have company_id index'
        );

        $this->assertTrue(
            $this->hasIndex('splits', ['transaction_id']),
            'splits table should have transaction_id index'
        );

        $this->assertTrue(
            $this->hasIndex('splits', ['account_id']),
            'splits table should have account_id index'
        );
    }

    protected function hasIndex(string $table, array $columns): bool
    {
        // For SQLite, check pragma index_list
        $indexes = \DB::select("PRAGMA index_list($table)");

        foreach ($indexes as $index) {
            $indexInfo = \DB::select("PRAGMA index_info({$index->name})");
            $indexColumns = collect($indexInfo)->pluck('name')->toArray();

            if (array_intersect($columns, $indexColumns) === $columns) {
                return true;
            }
        }

        // Also check for foreign keys which create implicit indexes
        $foreignKeys = \DB::select("PRAGMA foreign_key_list($table)");
        foreach ($foreignKeys as $fk) {
            if (in_array($fk->from, $columns)) {
                return true;
            }
        }

        return false;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function report_endpoints_respond_within_acceptable_time(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();

        $account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1100',
            'name' => 'Cash',
        ]);

        $incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4100',
            'name' => 'Revenue',
        ]);

        for ($i = 0; $i < 20; $i++) {
            $txn = Transaction::create([
                'company_id' => $this->company->id,
                'transaction_date' => now()->subDays($i),
                'description' => "Perf Transaction $i",
                'is_posted' => true,
                'posted_at' => now()->subDays($i),
                'post_date' => now()->subDays($i),
            ]);
            $txn->splits()->createMany([
                ['account_id' => $account->id, 'action' => 'DEBIT', 'amount_num' => 10000, 'amount_denom' => 100, 'value_num' => 10000, 'value_denom' => 100, 'reconciled_state' => 'n'],
                ['account_id' => $incomeAccount->id, 'action' => 'CREDIT', 'amount_num' => 10000, 'amount_denom' => 100, 'value_num' => 10000, 'value_denom' => 100, 'reconciled_state' => 'n'],
            ]);
        }

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $params = http_build_query(['company_id' => $this->company->id]);

        $start = microtime(true);
        $this->getJson("/api/reports/trial-balance?$params")->assertStatus(200);
        $trialMs = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $trialMs, 'Trial balance took too long');

        $start = microtime(true);
        $this->getJson("/api/reports/balance-sheet?$params")->assertStatus(200);
        $bsMs = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $bsMs, 'Balance sheet took too long');
    }
}
