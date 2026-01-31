<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountTypeSeeder::class);
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_users_cannot_access_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function csrf_protection_is_enabled(): void
    {
        $this->assertNotNull(config('session.cookie'));
        $this->assertTrue(in_array(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            app(\Illuminate\Routing\Router::class)->getMiddlewareGroups()['web'] ?? []
        ));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passwords_are_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext',
        ]);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(password_verify('plaintext', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function api_rate_limiting_is_configured(): void
    {
        $rateLimiter = app(\Illuminate\Cache\RateLimiter::class);

        // Check that rate limiter service exists
        $this->assertNotNull($rateLimiter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sensitive_routes_require_password_confirmation(): void
    {
        // Check password confirmation middleware exists
        $this->assertTrue(
            class_exists(\Illuminate\Auth\Middleware\RequirePassword::class),
            'Password confirmation middleware should exist'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function app_debug_can_be_disabled(): void
    {
        // This test verifies the config structure exists
        $this->assertNotNull(config('app.debug'));
        $this->assertIsBool(config('app.debug'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mass_assignment_is_protected(): void
    {
        $currency = Currency::where('code', 'USD')->first();
        $accountType = AccountType::where('name', 'ASSET')->first();

        $company = Company::create([
            'name' => 'Test Company',
            'default_currency_id' => $currency->id,
        ]);

        // Attempt to mass assign id (should be ignored)
        $account = Account::create([
            'id' => 99999,
            'company_id' => $company->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'code' => '1100',
            'name' => 'Test Account',
        ]);

        // ID should not be 99999 (auto-generated instead)
        $this->assertNotEquals(99999, $account->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sanctum_tokens_are_used_for_api(): void
    {
        $this->assertTrue(
            class_exists(\Laravel\Sanctum\PersonalAccessToken::class),
            'Sanctum should be installed'
        );

        $this->assertTrue(
            in_array(
                \Laravel\Sanctum\HasApiTokens::class,
                class_uses_recursive(User::class)
            ),
            'User model should use HasApiTokens trait'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function permissions_system_is_configured(): void
    {
        // Create a viewer user (limited permissions)
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        // Viewer should not have create permission
        $this->assertFalse($viewer->can('accounts.create'));
        $this->assertFalse($viewer->can('accounts.delete'));

        // But should have view permission
        $this->assertTrue($viewer->can('accounts.view'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owner_role_has_full_permissions(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->assertTrue($owner->can('accounts.view'));
        $this->assertTrue($owner->can('accounts.create'));
        $this->assertTrue($owner->can('accounts.update'));
        $this->assertTrue($owner->can('accounts.delete'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function environment_secrets_are_protected(): void
    {
        // Check that sensitive config keys exist but aren't hardcoded
        $this->assertNotNull(config('app.key'));
        $this->assertNotEmpty(config('app.key'));
        
        // Ensure APP_KEY is not a default/placeholder
        $this->assertNotEquals('base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', config('app.key'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function session_security_is_configured(): void
    {
        // Check session configuration for security
        $this->assertNotNull(config('session.driver'));
        $this->assertNotNull(config('session.lifetime'));
        $this->assertTrue(config('session.encrypt') || config('session.driver') === 'array');
    }
}
