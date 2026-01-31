<?php

namespace Tests\Unit\Models;

use App\Models\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_account_type(): void
    {
        $accountType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $this->assertDatabaseHas('account_types', [
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function test_name_must_be_unique(): void
    {
        AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'credit',
        ]);
    }

    public function test_it_has_accounts_relationship(): void
    {
        $accountType = AccountType::create([
            'name' => 'Asset',
            'normal_balance' => 'debit',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $accountType->accounts);
    }
}
