<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CurrencySeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountTypeSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    }

    public function test_user_can_view_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->assertSee('Email')
                ->assertSee('Password');
        });
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('owner');

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForLocation('/admin')
                ->assertPathIs('/admin');
        });
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', 'test@example.com')
                ->type('password', 'wrongpassword')
                ->press('Sign in')
                ->waitFor('.fi-fo-field-wrp-error-message')
                ->assertSee('credentials');
        });
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('owner');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->click('@user-menu')
                ->waitFor('@logout-button')
                ->click('@logout-button')
                ->waitForLocation('/admin/login')
                ->assertPathIs('/admin/login');
        });
    }
}
