<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
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
    }

    public function test_it_creates_transaction(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
            'num' => 'TXN-001',
        ]);

        $this->assertDatabaseHas('transactions', [
            'company_id' => $this->company->id,
            'description' => 'Test Transaction',
        ]);
    }

    public function test_it_belongs_to_company(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $this->assertInstanceOf(Company::class, $transaction->company);
    }

    public function test_it_can_have_splits(): void
    {
        $accountType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $cash = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $transaction->splits()->create([
            'account_id' => $cash->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $this->assertCount(1, $transaction->splits);
    }

    public function test_it_tracks_posted_status(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
            'is_posted' => false,
        ]);

        $this->assertFalse($transaction->is_posted);

        $transaction->update(['is_posted' => true, 'post_date' => now()]);

        $this->assertTrue($transaction->fresh()->is_posted);
        $this->assertNotNull($transaction->fresh()->post_date);
    }

    public function test_it_uses_casts_for_date(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->transaction_date);
        $this->assertEquals('2026-01-15', $transaction->transaction_date->format('Y-m-d'));
    }
}
