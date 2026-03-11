<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\BudgetAccount;
use App\Models\Company;
use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_budget(): void
    {
        $budget = Budget::factory()->create([
            'name' => '2026 Operating Budget',
            'fiscal_year' => 2026,
        ]);

        $this->assertDatabaseHas('budgets', [
            'name' => '2026 Operating Budget',
            'fiscal_year' => 2026,
        ]);
    }

    public function test_it_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $budget = Budget::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($budget->company->is($company));
    }

    public function test_it_has_accounts(): void
    {
        $budget = Budget::factory()->create();
        $accountType = AccountType::factory()->expense()->create();
        $account = Account::factory()->create([
            'company_id' => $budget->company_id,
            'account_type_id' => $accountType->id,
        ]);

        BudgetAccount::create([
            'budget_id' => $budget->id,
            'account_id' => $account->id,
            'period_num' => 1,
            'amount_num' => 100000,
            'amount_denom' => 100,
        ]);

        $this->assertCount(1, $budget->fresh()->accounts);
    }

    public function test_it_calculates_current_period(): void
    {
        $budget = Budget::factory()->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'num_periods' => 12,
            'recurrence' => 'monthly',
        ]);

        $currentPeriod = $budget->getCurrentPeriod();
        
        // Should be the current month
        $this->assertEquals(now()->month, $currentPeriod);
    }

    public function test_it_gets_period_dates(): void
    {
        $budget = Budget::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'num_periods' => 12,
            'recurrence' => 'monthly',
        ]);

        $dates = $budget->getPeriodDates(1);
        
        $this->assertEquals('2026-01-01', $dates['start']->format('Y-m-d'));
        $this->assertEquals('2026-01-31', $dates['end']->format('Y-m-d'));
    }

    public function test_quarterly_budget_periods(): void
    {
        $budget = Budget::factory()->quarterly()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $dates = $budget->getPeriodDates(1);
        
        $this->assertEquals('2026-01-01', $dates['start']->format('Y-m-d'));
        $this->assertEquals('2026-03-31', $dates['end']->format('Y-m-d'));
    }

    public function test_active_scope(): void
    {
        Budget::factory()->create(['is_active' => true]);
        Budget::factory()->create(['is_active' => false]);

        $this->assertCount(1, Budget::active()->get());
    }
}
