<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Split;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Currency $currency;
    protected Account $account;
    protected Transaction $transaction;

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

        $accountType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $this->account = Account::create([
            'company_id' => $this->company->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => '2026-01-15',
            'description' => 'Test Transaction',
        ]);
    }

    public function test_it_creates_split(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $this->assertDatabaseHas('splits', [
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'action' => 'DEBIT',
        ]);
    }

    public function test_it_belongs_to_transaction(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
        ]);

        $this->assertInstanceOf(Transaction::class, $split->transaction);
    }

    public function test_it_belongs_to_account(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'CREDIT',
        ]);

        $this->assertInstanceOf(Account::class, $split->account);
    }

    public function test_it_supports_negative_values(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => -10000,
            'amount_denom' => 100,
            'value_num' => -10000,
            'value_denom' => 100,
            'action' => 'CREDIT',
        ]);

        $this->assertEquals(-10000, $split->amount_num);
    }

    public function test_it_can_have_memo(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
            'memo' => 'Payment for services',
        ]);

        $this->assertEquals('Payment for services', $split->memo);
    }

    public function test_it_tracks_reconciliation_state(): void
    {
        $split = Split::create([
            'transaction_id' => $this->transaction->id,
            'account_id' => $this->account->id,
            'amount_num' => 10000,
            'amount_denom' => 100,
            'value_num' => 10000,
            'value_denom' => 100,
            'action' => 'DEBIT',
            'reconciled_state' => 'n',
        ]);

        $this->assertEquals('n', $split->reconciled_state);

        $split->update(['reconciled_state' => 'c']);
        $this->assertEquals('c', $split->fresh()->reconciled_state);

        $split->update(['reconciled_state' => 'y', 'reconcile_date' => now()]);
        $this->assertEquals('y', $split->fresh()->reconciled_state);
        $this->assertNotNull($split->fresh()->reconcile_date);
    }
}
