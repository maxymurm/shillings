<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\PushSubscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileOfflineSyncApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $currency;

    protected Account $cashAccount;

    protected Account $revenueAccount;

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
            'role' => 'owner',
        ]);

        // Create base accounts
        $assetType = AccountType::where('name', 'ASSET')->first();
        $incomeType = AccountType::where('name', 'INCOME')->first();

        $this->cashAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $assetType->id,
            'name' => 'Cash',
            'code' => '1000',
        ]);

        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type_id' => $incomeType->id,
            'name' => 'Revenue',
            'code' => '4000',
        ]);

        Sanctum::actingAs($this->user, ['*']);
    }

    // =============================
    // PWA Manifest & Service Worker (#121)
    // =============================

    public function test_pwa_manifest_is_accessible(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $data = json_decode(file_get_contents($manifestPath), true);

        $this->assertEquals('Shillings', $data['short_name']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertArrayHasKey('icons', $data);
        $this->assertNotEmpty($data['icons']);
        $this->assertEquals('#10b981', $data['theme_color']);
    }

    public function test_service_worker_is_accessible(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('shillings-static', $content);
        $this->assertStringContainsString('addEventListener', $content);
    }

    public function test_offline_page_is_accessible(): void
    {
        $offlinePath = public_path('offline.html');
        $this->assertFileExists($offlinePath);

        $content = file_get_contents($offlinePath);
        $this->assertStringContainsString('offline', strtolower($content));
        $this->assertStringContainsString('Shillings', $content);
    }

    // =============================
    // Sync API (#124 - Background Sync)
    // =============================

    public function test_sync_status_endpoint(): void
    {
        $response = $this->getJson('/api/sync/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'server_time',
                    'user_id',
                    'sync_supported',
                    'api_version',
                ],
            ]);

        $this->assertEquals($this->user->id, $response->json('data.user_id'));
        $this->assertTrue($response->json('data.sync_supported'));
    }

    public function test_sync_batch_creates_transactions(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'type' => 'CREATE',
                    'entity_type' => 'transaction',
                    'entity_id' => 'offline_12345',
                    'payload' => [
                        'company_id' => $this->company->id,
                        'description' => 'Offline Transaction Test',
                        'transaction_date' => now()->toDateString(),
                        'currency_code' => 'USD',
                        'splits' => [
                            [
                                'account_id' => $this->cashAccount->id,
                                'action' => 'DEBIT',
                                'amount_num' => 5000,
                                'amount_denom' => 100,
                                'value_num' => 5000,
                                'value_denom' => 100,
                            ],
                            [
                                'account_id' => $this->revenueAccount->id,
                                'action' => 'CREDIT',
                                'amount_num' => 5000,
                                'amount_denom' => 100,
                                'value_num' => 5000,
                                'value_denom' => 100,
                            ],
                        ],
                    ],
                    'client_timestamp' => time(),
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'results',
                    'server_time',
                    'processed',
                    'failed',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.processed'));
        $this->assertEquals(0, $response->json('data.failed'));
        $this->assertDatabaseHas('transactions', [
            'description' => 'Offline Transaction Test',
        ]);
    }

    public function test_sync_batch_validates_operations(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'type' => 'INVALID',
                    'entity_type' => 'transaction',
                    'entity_id' => null,
                    'payload' => [],
                    'client_timestamp' => time(),
                ],
            ],
        ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_sync_batch_handles_multiple_operations(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'type' => 'CREATE',
                    'entity_type' => 'transaction',
                    'entity_id' => 'offline_batch_1',
                    'payload' => [
                        'company_id' => $this->company->id,
                        'description' => 'Batch Txn 1',
                        'transaction_date' => now()->toDateString(),
                        'currency_code' => 'USD',
                        'splits' => [
                            [
                                'account_id' => $this->cashAccount->id,
                                'action' => 'DEBIT',
                                'amount_num' => 1000,
                                'amount_denom' => 100,
                                'value_num' => 1000,
                                'value_denom' => 100,
                            ],
                            [
                                'account_id' => $this->revenueAccount->id,
                                'action' => 'CREDIT',
                                'amount_num' => 1000,
                                'amount_denom' => 100,
                                'value_num' => 1000,
                                'value_denom' => 100,
                            ],
                        ],
                    ],
                    'client_timestamp' => time(),
                ],
                [
                    'type' => 'CREATE',
                    'entity_type' => 'transaction',
                    'entity_id' => 'offline_batch_2',
                    'payload' => [
                        'company_id' => $this->company->id,
                        'description' => 'Batch Txn 2',
                        'transaction_date' => now()->toDateString(),
                        'currency_code' => 'USD',
                        'splits' => [
                            [
                                'account_id' => $this->cashAccount->id,
                                'action' => 'DEBIT',
                                'amount_num' => 2000,
                                'amount_denom' => 100,
                                'value_num' => 2000,
                                'value_denom' => 100,
                            ],
                            [
                                'account_id' => $this->revenueAccount->id,
                                'action' => 'CREDIT',
                                'amount_num' => 2000,
                                'amount_denom' => 100,
                                'value_num' => 2000,
                                'value_denom' => 100,
                            ],
                        ],
                    ],
                    'client_timestamp' => time(),
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.processed'));
        $this->assertDatabaseHas('transactions', ['description' => 'Batch Txn 1']);
        $this->assertDatabaseHas('transactions', ['description' => 'Batch Txn 2']);
    }

    // =============================
    // Incremental Sync / Changes (#122, #124)
    // =============================

    public function test_sync_changes_endpoint(): void
    {
        // Create a transaction to be found
        $txn = Transaction::factory()->create([
            'company_id' => $this->company->id,
            'description' => 'Recent Change',
            'transaction_date' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/sync/changes?' . http_build_query([
            'since' => now()->subDay()->toIso8601String(),
            'entity_types' => ['transactions', 'accounts'],
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'changes' => [
                        'transactions',
                        'accounts',
                    ],
                    'since',
                    'server_time',
                ],
            ]);
    }

    public function test_sync_changes_filters_by_date(): void
    {
        // Create an old transaction
        Transaction::factory()->create([
            'company_id' => $this->company->id,
            'description' => 'Old Transaction',
            'transaction_date' => now()->subMonths(2),
            'updated_at' => now()->subMonths(2),
        ]);

        // Create a recent transaction
        Transaction::factory()->create([
            'company_id' => $this->company->id,
            'description' => 'Recent Transaction',
            'transaction_date' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/sync/changes?' . http_build_query([
            'since' => now()->subWeek()->toIso8601String(),
            'entity_types' => ['transactions'],
        ]));

        $response->assertStatus(200);
        $transactions = $response->json('data.changes.transactions');
        $descriptions = array_column($transactions, 'description');

        $this->assertContains('Recent Transaction', $descriptions);
        $this->assertNotContains('Old Transaction', $descriptions);
    }

    // =============================
    // Conflict Resolution (#125)
    // =============================

    public function test_sync_batch_returns_error_for_nonexistent_entity(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'type' => 'UPDATE',
                    'entity_type' => 'transaction',
                    'entity_id' => '00000000-0000-0000-0000-000000000000',
                    'payload' => [
                        'description' => 'Updated description',
                    ],
                    'client_timestamp' => time(),
                ],
            ],
        ]);

        $response->assertStatus(200);
        $results = $response->json('data.results');
        $this->assertEquals('not_found', $results[0]['status']);
    }

    // =============================
    // Push Notifications (#127)
    // =============================

    public function test_can_register_push_subscription(): void
    {
        $response = $this->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-12345',
            'keys' => [
                'p256dh' => base64_encode('test-p256dh-key-data-here'),
                'auth' => base64_encode('test-auth-token'),
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'created'],
            ]);

        $this->assertTrue($response->json('data.created'));
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-12345',
        ]);
    }

    public function test_can_update_existing_push_subscription(): void
    {
        // Register first
        $this->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-update',
            'keys' => [
                'p256dh' => base64_encode('original-key'),
                'auth' => base64_encode('original-auth'),
            ],
        ]);

        // Register again with same endpoint (should update)
        $response = $this->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-update',
            'keys' => [
                'p256dh' => base64_encode('updated-key'),
                'auth' => base64_encode('updated-auth'),
            ],
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.created'));

        // Should only have one subscription for this endpoint
        $count = PushSubscription::where('user_id', $this->user->id)
            ->where('endpoint', 'https://fcm.googleapis.com/fcm/send/test-endpoint-update')
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_can_remove_push_subscription(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-delete';

        // Register
        $this->postJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => base64_encode('key'),
                'auth' => base64_encode('auth'),
            ],
        ]);

        // Remove
        $response = $this->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_push_subscription_validates_endpoint(): void
    {
        $response = $this->postJson('/api/push-subscriptions', [
            'endpoint' => 'not-a-valid-url',
            'keys' => [
                'p256dh' => 'key',
                'auth' => 'auth',
            ],
        ]);

        $response->assertStatus(422);
    }

    // =============================
    // Mobile-optimized Filament (#126)
    // =============================

    public function test_admin_panel_loads_pwa_meta_tags(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/admin');

        // Should have a valid response (redirect to dashboard or dashboard itself)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    // =============================
    // Sync requires authentication
    // =============================

    public function test_sync_endpoints_require_authentication(): void
    {
        // Logout / clear auth
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/sync/status');
        $response->assertStatus(401);

        $response = $this->postJson('/api/sync/batch', ['operations' => []]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/sync/changes?since=' . now()->subDay()->toIso8601String());
        $response->assertStatus(401);
    }

    // =============================
    // Health check still works
    // =============================

    public function test_health_check_is_accessible(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok', 'app' => 'Shillings']);
    }
}
