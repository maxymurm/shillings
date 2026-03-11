<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Budget;
use App\Models\BudgetAccount;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Account $expenseAccount;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
        ]);
        
        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $currency->id,
        ]);
        
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'company_id' => $this->company->id,
        ]);
        
        $expenseType = AccountType::factory()->expense()->create();
        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
        ]);
        
        $this->token = $this->user->createToken('test-token', [
            'budgets:read',
            'budgets:create',
            'budgets:update',
            'budgets:delete',
        ])->plainTextToken;
    }

    public function test_it_lists_budgets(): void
    {
        Budget::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->withToken($this->token)->getJson('/api/budgets');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_fiscal_year(): void
    {
        Budget::factory()->create(['company_id' => $this->company->id, 'fiscal_year' => 2025]);
        Budget::factory()->create(['company_id' => $this->company->id, 'fiscal_year' => 2026]);

        $response = $this->withToken($this->token)->getJson('/api/budgets?fiscal_year=2026');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.fiscal_year', 2026);
    }

    public function test_it_creates_budget(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/budgets', [
            'name' => 'Annual Budget 2026',
            'fiscal_year' => 2026,
            'num_periods' => 12,
            'recurrence' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Annual Budget 2026')
            ->assertJsonPath('data.fiscal_year', 2026);

        $this->assertDatabaseHas('budgets', [
            'name' => 'Annual Budget 2026',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_it_creates_budget_with_accounts(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/budgets', [
            'name' => 'Expense Budget 2026',
            'fiscal_year' => 2026,
            'num_periods' => 12,
            'recurrence' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'accounts' => [
                [
                    'account_id' => $this->expenseAccount->id,
                    'period_num' => 1,
                    'amount' => 10000.00,
                ],
                [
                    'account_id' => $this->expenseAccount->id,
                    'period_num' => 2,
                    'amount' => 12000.00,
                ],
            ],
        ]);

        $response->assertCreated();

        $budget = Budget::find($response->json('data.id'));
        $this->assertCount(2, $budget->budgetAccounts);
    }

    public function test_it_shows_budget(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Budget',
        ]);

        $response = $this->withToken($this->token)->getJson("/api/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Test Budget');
    }

    public function test_it_updates_budget(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        $response = $this->withToken($this->token)->putJson("/api/budgets/{$budget->id}", [
            'name' => 'Updated Budget Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Budget Name');
    }

    public function test_it_deletes_budget(): void
    {
        $budget = Budget::factory()->create(['company_id' => $this->company->id]);
        
        // Add budget accounts
        BudgetAccount::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $this->expenseAccount->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/budgets/{$budget->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Budget deleted successfully');

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseMissing('budget_accounts', ['budget_id' => $budget->id]);
    }

    public function test_it_clones_budget(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Budget 2025',
            'fiscal_year' => 2025,
        ]);

        BudgetAccount::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $this->expenseAccount->id,
            'period_num' => 1,
            'amount_num' => 1000000,
            'amount_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/budgets/{$budget->id}/clone", [
            'new_year' => 2026,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.fiscal_year', 2026);

        $newBudget = Budget::find($response->json('data.id'));
        $this->assertEquals('Budget 2026', $newBudget->name);
        $this->assertCount(1, $newBudget->accounts);
    }

    public function test_it_gets_budget_vs_actual(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'fiscal_year' => now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        BudgetAccount::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $this->expenseAccount->id,
            'period_num' => 1,
            'amount_num' => 1000000,
            'amount_denom' => 100,
        ]);

        $response = $this->withToken($this->token)->getJson("/api/budgets/{$budget->id}/vs-actual");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'periods' => [
                        '*' => [
                            'period',
                            'budgeted_num',
                            'budgeted_denom',
                            'actual_num',
                            'actual_denom',
                            'variance_num',
                            'variance_denom',
                            'variance_percent',
                        ],
                    ],
                    'totals',
                ],
            ]);
    }

    public function test_it_gets_budget_utilization(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
        ]);

        BudgetAccount::factory()->create([
            'budget_id' => $budget->id,
            'account_id' => $this->expenseAccount->id,
        ]);

        $response = $this->withToken($this->token)->getJson("/api/budgets/{$budget->id}/utilization");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'accounts' => [
                        '*' => [
                            'account_id',
                            'account_name',
                            'budgeted_num',
                            'budgeted_denom',
                            'actual_num',
                            'actual_denom',
                            'utilization_percent',
                        ],
                    ],
                    'overall_utilization',
                ],
            ]);
    }

    public function test_it_gets_budget_forecast(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        $response = $this->withToken($this->token)->getJson("/api/budgets/{$budget->id}/forecast");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'projected_total_num',
                    'projected_total_denom',
                    'remaining_budget_num',
                    'remaining_budget_denom',
                    'run_rate',
                    'forecast_by_period',
                ],
            ]);
    }

    public function test_it_distributes_amount(): void
    {
        $budget = Budget::factory()->create([
            'company_id' => $this->company->id,
            'num_periods' => 12,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/budgets/{$budget->id}/distribute", [
            'account_id' => $this->expenseAccount->id,
            'annual_amount' => 120000.00,
            'method' => 'equal',
        ]);

        $response->assertOk();

        $budget->refresh();
        $this->assertCount(12, $budget->budgetAccounts);
        
        // Each period should have 10,000 (120,000 / 12)
        $firstPeriod = $budget->budgetAccounts->where('period_num', 1)->first();
        // Check the actual value equals 10000.00 (using fraction: amount_num / amount_denom)
        $this->assertEquals(10000.00, $firstPeriod->amount_num / $firstPeriod->amount_denom);
    }

    public function test_it_gets_active_budgets(): void
    {
        Budget::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        Budget::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/budgets/active');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_quarterly_budget_creation(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/budgets', [
            'name' => 'Quarterly Budget 2026',
            'fiscal_year' => 2026,
            'num_periods' => 4,
            'recurrence' => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.num_periods', 4)
            ->assertJsonPath('data.recurrence', 'quarterly');
    }

    public function test_validation_requires_name(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/budgets', [
            'fiscal_year' => 2026,
            'num_periods' => 12,
            'recurrence' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            // Missing name
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
