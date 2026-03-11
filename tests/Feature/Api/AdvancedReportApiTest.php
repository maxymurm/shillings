<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Document;
use App\Models\Split;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Currency $currency;
    protected Account $cashAccount;
    protected Account $arAccount;
    protected Account $apAccount;
    protected Account $revenueAccount;
    protected Account $expenseAccount;
    protected Account $equityAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('db:seed', ['--class' => 'CurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        $this->currency = Currency::where('code', 'USD')->first();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('owner');
        
        $this->company = Company::factory()->create([
            'default_currency_id' => $this->currency->id,
        ]);
        $this->company->users()->attach($this->user->id, [
            'id' => Str::uuid()->toString(),
        ]);

        $this->createAccounts();
        
        Sanctum::actingAs($this->user, [
            'reports:read',
            'reports:create',
            'accounts:read',
            'transactions:read',
        ]);
    }

    protected function createAccounts(): void
    {
        $assetType = AccountType::where('name', 'ASSET')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();
        $expenseType = AccountType::where('name', 'EXPENSE')->first();
        $equityType = AccountType::where('name', 'EQUITY')->first();

        $this->cashAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1000',
            'name' => 'Cash',
        ]);

        $this->arAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
        ]);

        $this->apAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => AccountType::where('name', 'LIABILITY')->first()->id,
            'currency_id' => $this->currency->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
        ]);

        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'currency_id' => $this->currency->id,
            'code' => '4000',
            'name' => 'Revenue',
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'code' => '5000',
            'name' => 'Expenses',
        ]);

        $this->equityAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $equityType->id,
            'currency_id' => $this->currency->id,
            'code' => '3000',
            'name' => 'Owner\'s Equity',
        ]);
    }

    public function test_it_can_generate_general_ledger_report(): void
    {
        // Create some transactions
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subDays(5));
        $this->createTransaction($this->expenseAccount, $this->cashAccount, 300, now()->subDays(3));

        $response = $this->getJson('/api/reports/general-ledger?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'accounts' => [
                        '*' => [
                            'account_id',
                            'account_code',
                            'account_name',
                            'opening_balance',
                            'period_debit',
                            'period_credit',
                            'closing_balance',
                            'transactions',
                        ],
                    ],
                    'totals',
                    'period',
                ],
            ]);
    }

    public function test_it_can_filter_general_ledger_by_account_types(): void
    {
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subDays(5));

        $response = $this->getJson('/api/reports/general-ledger?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
            'account_types' => ['Asset'],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data.accounts');
        
        foreach ($data as $account) {
            $this->assertEquals('ASSET', $account['account_type']);
        }
    }

    public function test_it_can_filter_general_ledger_by_specific_accounts(): void
    {
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subDays(5));
        $this->createTransaction($this->expenseAccount, $this->cashAccount, 300, now()->subDays(3));

        $response = $this->getJson('/api/reports/general-ledger?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
            'account_ids' => [$this->cashAccount->id],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data.accounts');
        
        $this->assertCount(1, $data);
        $this->assertEquals($this->cashAccount->id, $data[0]['account_id']);
    }

    public function test_it_can_generate_account_register_report(): void
    {
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subDays(5));
        $this->createTransaction($this->expenseAccount, $this->cashAccount, 300, now()->subDays(3));

        $response = $this->getJson('/api/reports/account-register?' . http_build_query([
            'account_id' => $this->cashAccount->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'transaction_date',
                        'description',
                        'action',
                        'amount_num',
                        'amount_denom',
                        'running_balance',
                    ],
                ],
                'metadata',
                'pagination',
            ]);
    }

    public function test_it_can_search_account_register(): void
    {
        $txn1 = $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subDays(5), 'Sales Revenue');
        $txn2 = $this->createTransaction($this->expenseAccount, $this->cashAccount, 300, now()->subDays(3), 'Office Supplies');

        $response = $this->getJson('/api/reports/account-register?' . http_build_query([
            'account_id' => $this->cashAccount->id,
            'search' => 'Revenue',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('Sales Revenue', json_encode($response->json('data')));
        $this->assertStringNotContainsString('Office Supplies', json_encode($response->json('data')));
    }

    public function test_it_can_paginate_account_register(): void
    {
        // Create 15 transactions
        for ($i = 0; $i < 15; $i++) {
            $this->createTransaction($this->cashAccount, $this->revenueAccount, 100, now()->subDays($i));
        }

        $response = $this->getJson('/api/reports/account-register?' . http_build_query([
            'account_id' => $this->cashAccount->id,
            'per_page' => 10,
            'page' => 1,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 15)
            ->assertJsonPath('pagination.last_page', 2);
    }

    public function test_it_can_generate_accounts_receivable_aging_report(): void
    {
        // Create customer with overdue invoices
        $customer = Contact::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        $this->createInvoice($customer, 1000, now()->subDays(75)); // 31-60 days (issued 75 days ago, due 45 days ago)
        $this->createInvoice($customer, 500, now()->subDays(105)); // 61-90 days (issued 105 days ago, due 75 days ago)

        $response = $this->getJson('/api/reports/ar-aging?' . http_build_query([
            'company_id' => $this->company->id,
            'as_of_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'contacts' => [
                        '*' => [
                            'contact_id',
                            'contact_name',
                            'current',
                            '1_30_days',
                            '31_60_days',
                            '61_90_days',
                            'over_90_days',
                            'total',
                        ],
                    ],
                    'totals',
                ],
            ]);

        // Verify amounts are in correct aging buckets
        $contacts = $response->json('data.contacts');
        $this->assertNotEmpty($contacts, 'No contacts returned in aging report');
        $contact = $contacts[0];
        $this->assertGreaterThan(0, $contact['31_60_days']); // $1000 invoice
        $this->assertGreaterThan(0, $contact['61_90_days']); // $500 invoice
    }

    public function test_it_can_generate_accounts_payable_aging_report(): void
    {
        // Create vendor with overdue bills
        $vendor = Contact::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $this->createBill($vendor, 800, now()->subDays(75)); // 31-60 days (issued 75 days ago, due 45 days ago)
        $this->createBill($vendor, 300, now()->subDays(155)); // Over 90 days (issued 155 days ago, due 125 days ago)

        $response = $this->getJson('/api/reports/ap-aging?' . http_build_query([
            'company_id' => $this->company->id,
            'as_of_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'contacts' => [
                        '*' => [
                            'contact_id',
                            'contact_name',
                            'current',
                            '1_30_days',
                            '31_60_days',
                            '61_90_days',
                            'over_90_days',
                            'total',
                        ],
                    ],
                    'totals',
                ],
            ]);

        $contact = $response->json('data.contacts.0');
        $this->assertGreaterThan(0, $contact['31_60_days']); // $800 bill
        $this->assertGreaterThan(0, $contact['over_90_days']); // $300 bill
    }

    public function test_it_can_generate_tax_summary_report(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'VAT',
            'rate_num' => 1500,
            'rate_denom' => 100,
            'enabled' => true,
        ]);

        // Create transactions with tax
        $this->createTransactionWithTax($this->cashAccount, $this->revenueAccount, 1000, $tax, now()->subDays(5));
        $this->createTransactionWithTax($this->expenseAccount, $this->cashAccount, 500, $tax, now()->subDays(3));

        $response = $this->getJson('/api/reports/tax-summary?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'taxes' => [
                        '*' => [
                            'tax_id',
                            'tax_name',
                            'tax_rate',
                            'collected',
                            'paid',
                            'net',
                        ],
                    ],
                    'totals',
                ],
            ]);
    }

    public function test_it_can_generate_equity_statement_report(): void
    {
        // Create transactions affecting equity
        $this->createTransaction($this->cashAccount, $this->equityAccount, 5000, now()->startOfYear()); // Opening balance
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 2000, now()->subMonth()); // Revenue (increases equity)
        $this->createTransaction($this->expenseAccount, $this->cashAccount, 800, now()->subWeeks(2)); // Expense (decreases equity)

        $response = $this->getJson('/api/reports/equity-statement?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'opening_equity',
                    'net_income',
                    'changes' => [
                        '*' => [
                            'description',
                            'amount',
                        ],
                    ],
                    'closing_equity',
                ],
            ]);
    }

    public function test_it_can_compare_reports_across_periods(): void
    {
        // Create transactions in different periods
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1000, now()->subMonths(2));
        $this->createTransaction($this->cashAccount, $this->revenueAccount, 1500, now()->subMonth());

        $response = $this->postJson('/api/reports/income-statement/compare', [
            'company_id' => $this->company->id,
            'periods' => [
                [
                    'start_date' => now()->subMonths(2)->startOfMonth()->toDateString(),
                    'end_date' => now()->subMonths(2)->endOfMonth()->toDateString(),
                ],
                [
                    'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
                    'end_date' => now()->subMonth()->endOfMonth()->toDateString(),
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'periods' => [
                        '*' => [
                            'start_date',
                            'end_date',
                            'revenue',
                            'expenses',
                            'net_income',
                        ],
                    ],
                    'variance' => [
                        'revenue_change',
                        'revenue_change_percent',
                        'expenses_change',
                        'net_income_change',
                    ],
                ],
            ]);
    }

    public function test_it_validates_general_ledger_parameters(): void
    {
        $response = $this->getJson('/api/reports/general-ledger?' . http_build_query([
            'company_id' => 'invalid-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_it_validates_account_register_parameters(): void
    {
        $response = $this->getJson('/api/reports/account-register?' . http_build_query([
            'account_id' => 'invalid-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id']);
    }

    public function test_it_validates_date_ranges(): void
    {
        $response = $this->getJson('/api/reports/general-ledger?' . http_build_query([
            'company_id' => $this->company->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(), // End before start
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // Helper methods

    protected function createTransaction(
        Account $debitAccount,
        Account $creditAccount,
        float $amount,
        $date,
        string $description = 'Test transaction'
    ): Transaction {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => $date,
            'description' => $description,
            'is_posted' => true,
            'post_date' => $date,
        ]);

        $amountCents = (int) ($amount * 100);

        $transaction->splits()->createMany([
            [
                'account_id' => $debitAccount->id,
                'action' => Split::DEBIT,
                'amount_num' => $amountCents,
                'amount_denom' => 100,
                'value_num' => $amountCents,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $creditAccount->id,
                'action' => Split::CREDIT,
                'amount_num' => $amountCents,
                'amount_denom' => 100,
                'value_num' => $amountCents,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
        ]);

        return $transaction;
    }

    protected function createInvoice(Contact $customer, float $amount, $issuedAt): Document
    {
        $amountCents = (int) ($amount * 100);
        
        $invoice = Document::factory()->invoice()->create([
            'company_id' => $this->company->id,
            'contact_id' => $customer->id,
            'issued_at' => $issuedAt,
            'due_at' => $issuedAt->copy()->addDays(30),
            'currency_code' => $this->currency->code,
            'total_num' => $amountCents,
            'total_denom' => 100,
            'subtotal_num' => $amountCents,
            'subtotal_denom' => 100,
            'status' => 'sent',
        ]);

        // Create associated A/R transaction
        $transaction = $this->createTransaction(
            $this->arAccount,
            $this->revenueAccount,
            $amount,
            $issuedAt,
            "Invoice {$invoice->document_number}"
        );

        $invoice->update(['transaction_id' => $transaction->id]);

        return $invoice;
    }

    protected function createBill(Contact $vendor, float $amount, $issuedAt): Document
    {
        $amountCents = (int) ($amount * 100);
        
        $bill = Document::factory()->bill()->create([
            'company_id' => $this->company->id,
            'contact_id' => $vendor->id,
            'issued_at' => $issuedAt,
            'due_at' => $issuedAt->copy()->addDays(30),
            'currency_code' => $this->currency->code,
            'total_num' => $amountCents,
            'total_denom' => 100,
            'subtotal_num' => $amountCents,
            'subtotal_denom' => 100,
            'status' => 'sent',
        ]);

        // Create associated A/P transaction
        $transaction = $this->createTransaction(
            $this->expenseAccount,
            $this->apAccount,
            $amount,
            $issuedAt,
            "Bill {$bill->document_number}"
        );

        $bill->update(['transaction_id' => $transaction->id]);

        return $bill;
    }

    protected function createTransactionWithTax(
        Account $debitAccount,
        Account $creditAccount,
        float $amount,
        Tax $tax,
        $date
    ): Transaction {
        $transaction = Transaction::create([
            'company_id' => $this->company->id,
            'transaction_date' => $date,
            'description' => 'Transaction with tax',
            'is_posted' => true,
            'post_date' => $date,
        ]);

        $amountCents = (int) ($amount * 100);
        $taxAmount = (int) ($amount * ($tax->rate / 100) * 100);

        $transaction->splits()->createMany([
            [
                'account_id' => $debitAccount->id,
                'action' => Split::DEBIT,
                'amount_num' => $amountCents + $taxAmount,
                'amount_denom' => 100,
                'value_num' => $amountCents + $taxAmount,
                'value_denom' => 100,
                'reconciled_state' => 'n',
            ],
            [
                'account_id' => $creditAccount->id,
                'action' => Split::CREDIT,
                'amount_num' => $amountCents,
                'amount_denom' => 100,
                'value_num' => $amountCents,
                'value_denom' => 100,
                'reconciled_state' => 'n',
                'tax_id' => $tax->id,
            ],
        ]);

        return $transaction;
    }
}
