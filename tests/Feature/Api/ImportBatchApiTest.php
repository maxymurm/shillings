<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportBatchApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Account $bankAccount;
    protected Account $expenseAccount;
    protected Currency $currency;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
        ]);
        
        $this->company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $this->currency->id,
        ]);
        
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'company_id' => $this->company->id,
        ]);
        
        $assetType = AccountType::factory()->asset()->create();
        $expenseType = AccountType::factory()->expense()->create();
        
        $this->bankAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Bank Account',
            'code' => '1000',
        ]);
        
        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $expenseType->id,
            'currency_id' => $this->currency->id,
            'name' => 'Misc Expense',
            'code' => '5000',
        ]);
        
        $this->token = $this->user->createToken('test-token', [
            'imports:read',
            'imports:create',
            'imports:update',
            'imports:delete',
        ])->plainTextToken;

        Storage::fake('local');
    }

    public function test_it_lists_import_batches(): void
    {
        ImportBatch::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/imports');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_status(): void
    {
        ImportBatch::factory()->pending()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);
        
        ImportBatch::factory()->completed()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->getJson('/api/imports?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_it_shows_import_batch(): void
    {
        $batch = ImportBatch::factory()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->getJson("/api/imports/{$batch->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $batch->id)
            ->assertJsonPath('data.account.id', $this->bankAccount->id);
    }

    public function test_it_previews_csv_file(): void
    {
        $csvContent = "Date,Amount,Description\n2026-01-01,1000.00,Test Transaction\n2026-01-02,-500.50,Another Test";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->withToken($this->token)->postJson('/api/imports/preview-csv', [
            'file' => $file,
            'rows' => 2,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'headers',
                    'rows',
                    'total_rows',
                ],
            ])
            ->assertJsonPath('data.headers.0', 'Date')
            ->assertJsonPath('data.headers.1', 'Amount')
            ->assertJsonPath('data.headers.2', 'Description');
    }

    public function test_it_uploads_csv_file(): void
    {
        $csvContent = "Date,Amount,Description\n2026-01-01,1000.00,Test Transaction\n2026-01-02,-500.50,Another Test";
        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->withToken($this->token)->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->bankAccount->id,
            'file_type' => 'csv',
            'mapping' => [
                'date' => 'Date',
                'amount' => 'Amount',
                'description' => 'Description',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'batch',
                    'transaction_count',
                    'preview',
                ],
            ])
            ->assertJsonPath('data.transaction_count', 2);

        $this->assertDatabaseHas('import_batches', [
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
            'file_name' => 'transactions.csv',
            'type' => 'csv',
            'status' => 'pending',
            'total_rows' => 2,
        ]);
    }

    public function test_it_uploads_ofx_file(): void
    {
        $ofxContent = <<<OFX
<OFX>
<BANKMSGSRSV1>
<STMTTRNRS>
<STMTRS>
<BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20260101</DTPOSTED>
<TRNAMT>-1000.00</TRNAMT>
<FITID>TXN001</FITID>
<NAME>Test Merchant</NAME>
<MEMO>Test purchase</MEMO>
</STMTTRN>
</BANKTRANLIST>
</STMTRS>
</STMTTRNRS>
</BANKMSGSRSV1>
</OFX>
OFX;
        $file = UploadedFile::fake()->createWithContent('transactions.ofx', $ofxContent);

        $response = $this->withToken($this->token)->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->bankAccount->id,
            'file_type' => 'ofx',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.transaction_count', 1);

        $this->assertDatabaseHas('import_batches', [
            'company_id' => $this->company->id,
            'file_name' => 'transactions.ofx',
            'type' => 'ofx',
            'status' => 'pending',
        ]);
    }

    public function test_it_processes_import_batch(): void
    {
        $batch = ImportBatch::factory()->pending()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
            'total_rows' => 2,
        ]);

        $transactions = [
            [
                'line_number' => 1,
                'date' => '2026-01-01',
                'amount_num' => 100000,
                'amount_denom' => 100,
                'is_debit' => false,
                'description' => 'Test Deposit',
                'reference' => 'TXN001',
            ],
            [
                'line_number' => 2,
                'date' => '2026-01-02',
                'amount_num' => 50050,
                'amount_denom' => 100,
                'is_debit' => true,
                'description' => 'Test Withdrawal',
                'reference' => 'TXN002',
            ],
        ];

        Cache::put("import_batch_{$batch->id}", $transactions, now()->addHour());

        $response = $this->withToken($this->token)->postJson("/api/imports/{$batch->id}/process", [
            'offset_account_id' => $this->expenseAccount->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.created_count', 2);

        $this->assertDatabaseHas('import_batches', [
            'id' => $batch->id,
            'status' => 'completed',
            'created_count' => 2,
        ]);
    }

    public function test_it_cannot_process_already_processed_batch(): void
    {
        $batch = ImportBatch::factory()->completed()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->postJson("/api/imports/{$batch->id}/process", [
            'offset_account_id' => $this->expenseAccount->id,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Batch has already been processed']);
    }

    public function test_it_handles_expired_import_data(): void
    {
        $batch = ImportBatch::factory()->pending()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        // Don't put anything in cache - simulate expired data

        $response = $this->withToken($this->token)->postJson("/api/imports/{$batch->id}/process", [
            'offset_account_id' => $this->expenseAccount->id,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Import data has expired. Please upload the file again.']);
    }

    public function test_it_matches_transactions(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/imports/match', [
            'account_id' => $this->bankAccount->id,
            'transactions' => [
                [
                    'date' => '2026-01-01',
                    'amount_num' => 100000,
                    'amount_denom' => 100,
                    'is_debit' => false,
                    'description' => 'Test Transaction',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'imported',
                        'matches',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_it_deletes_import_batch(): void
    {
        $batch = ImportBatch::factory()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/imports/{$batch->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Import batch deleted successfully']);

        $this->assertDatabaseMissing('import_batches', [
            'id' => $batch->id,
        ]);
    }

    public function test_it_cannot_delete_processing_batch(): void
    {
        $batch = ImportBatch::factory()->processing()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        $response = $this->withToken($this->token)->deleteJson("/api/imports/{$batch->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete batch that is currently processing']);

        $this->assertDatabaseHas('import_batches', [
            'id' => $batch->id,
        ]);
    }

    public function test_validation_requires_file(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/imports/upload', [
            'account_id' => $this->bankAccount->id,
            'file_type' => 'csv',
            'mapping' => ['date' => 'Date'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_validation_requires_mapping_for_csv(): void
    {
        $file = UploadedFile::fake()->create('test.csv');

        $response = $this->withToken($this->token)->postJson('/api/imports/upload', [
            'file' => $file,
            'account_id' => $this->bankAccount->id,
            'file_type' => 'csv',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mapping']);
    }

    public function test_validation_requires_offset_account_for_processing(): void
    {
        $batch = ImportBatch::factory()->pending()->create([
            'company_id' => $this->company->id,
            'account_id' => $this->bankAccount->id,
        ]);

        Cache::put("import_batch_{$batch->id}", [], now()->addHour());

        $response = $this->withToken($this->token)->postJson("/api/imports/{$batch->id}/process");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset_account_id']);
    }
}
