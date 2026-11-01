<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['name' => 'Fatma Ben Ali', 'email' => 'fatma@example.com']);
    }

    public function test_profile_page_requires_login_and_shows_user(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->actingAs($this->user)->get('/profile')->assertOk()
            ->assertSee('Mon profil')->assertSee('fatma@example.com')->assertSee('FB')->assertSee('Activer la double authentification');
    }

    public function test_user_updates_name_photo_and_email(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $this->put('/profile', ['name' => 'Fatma B.', 'email' => 'fatma@example.com', 'avatar' => UploadedFile::fake()->create('me.jpg', 200, 'image/jpeg')])
            ->assertSessionHasNoErrors();
        $this->user->refresh();
        $this->assertSame('Fatma B.', $this->user->name);
        Storage::disk('public')->assertExists(substr($this->user->avatar, 8));

        // Changing the e-mail needs the current password.
        $this->put('/profile', ['name' => 'Fatma B.', 'email' => 'new@example.com'])->assertSessionHasErrorsIn('profile', 'current_password');
        $this->put('/profile', ['name' => 'Fatma B.', 'email' => 'new@example.com', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('new@example.com', $this->user->fresh()->email);

        $old = substr($this->user->fresh()->avatar, 8);
        $this->put('/profile', ['name' => 'Fatma B.', 'email' => 'new@example.com', 'remove_avatar' => 1]);
        $this->assertNull($this->user->fresh()->avatar);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_user_changes_password(): void
    {
        $this->actingAs($this->user);
        $this->put('/profile/password', ['current_password' => 'wrong', 'password' => 'nouveau123', 'password_confirmation' => 'nouveau123'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->put('/profile/password', ['current_password' => 'password', 'password' => 'nouveau123', 'password_confirmation' => 'nouveau123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('nouveau123', $this->user->fresh()->password));
    }

    public function test_user_deletes_account_but_admin_and_renters_cannot(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        $equipment = Equipment::create(['title' => 'Batterie', 'description' => 'Test', 'condition' => 'Bon état', 'price_per_day' => 20, 'category_id' => $category->id, 'owner_id' => $admin->id]);
        Incident::create(['equipment_id' => $equipment->id, 'user_id' => $this->user->id, 'description' => 'Ne charge plus.', 'severity' => 'moyen']);

        $this->actingAs($admin)->delete('/profile', ['delete_password' => 'password'])->assertSessionHasErrorsIn('deletion', 'delete_password');
        $this->assertModelExists($admin);

        $this->actingAs($this->user)->delete('/profile', ['delete_password' => 'wrong'])->assertSessionHasErrorsIn('deletion', 'delete_password');
        $this->delete('/profile', ['delete_password' => 'password'])->assertRedirect('/');
        $this->assertModelMissing($this->user);
        $this->assertSame(0, Incident::count());
        $this->assertGuest();
    }

    public function test_remember_me_sets_the_remember_cookie(): void
    {
        $this->post('/login', ['email' => 'fatma@example.com', 'password' => 'password', 'remember' => '1'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->user);
        $this->assertNotNull($this->user->fresh()->remember_token);
    }

    public function test_two_factor_setup_login_and_recovery_code(): void
    {
        // Many logins in one test: the app's rate limit (shared per visitor) would block them.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $google2fa = new Google2FA;
        $this->actingAs($this->user);

        $this->post('/profile/two-factor', ['current_password' => 'wrong'])->assertSessionHasErrorsIn('twoFactor', 'current_password');
        $this->post('/profile/two-factor', ['current_password' => 'password'])->assertRedirect();
        $secret = $this->user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
        $this->get('/profile')->assertSee('<svg', false)->assertSee('Configuration en cours');

        $this->post('/profile/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrorsIn('twoFactor', 'code');
        $this->post('/profile/two-factor/confirm', ['code' => $google2fa->getCurrentOtp($secret)])->assertSessionHasNoErrors();
        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $this->user->fresh()->two_factor_recovery_codes);

        // Login now asks for the code before opening the session.
        Auth::logout();
        $this->post('/login', ['email' => 'fatma@example.com', 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->get('/two-factor-challenge')->assertOk()->assertSee('Entrez votre code');
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['code' => $google2fa->getCurrentOtp($secret)])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->user);

        // A recovery code works once.
        Auth::logout();
        $recovery = $this->user->fresh()->two_factor_recovery_codes[0];
        $this->post('/login', ['email' => 'fatma@example.com', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/');
        $this->assertCount(7, $this->user->fresh()->two_factor_recovery_codes);
        Auth::logout();
        $this->post('/login', ['email' => 'fatma@example.com', 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Without a pending login the challenge page sends back to login.
        $this->flushSession();
        $this->get('/two-factor-challenge')->assertRedirect('/login');

        $this->actingAs($this->user)->delete('/profile/two-factor', ['current_password' => 'password']);
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
    }
}
