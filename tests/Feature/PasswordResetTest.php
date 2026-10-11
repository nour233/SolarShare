<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_email_and_single_use_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'old-password']);
        $this->get('/login')->assertSee('Mot de passe oublié');
        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function ($notification) use (&$code) {
            $code = $notification->code;
            return true;
        });
        $this->get('/forgot-password/code')->assertOk();
        $this->post('/forgot-password/code', ['code' => '000000'])->assertSessionHasErrors('code');
        $response = $this->post('/forgot-password/code', ['code' => $code]);
        $response->assertRedirect();
        $url = $response->headers->get('Location');
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->get($url)->assertOk();
        $data = ['token' => $token, 'email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/reset-password', $data)->assertRedirect('/login')->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_expiry_validation_and_unknown_email(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'absent@example.com'])->assertSessionHas('status');
        Notification::assertNothingSent();
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $data = ['token' => $token, 'email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'different'];
        $this->post('/reset-password', $data)->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $data['password_confirmation'] = $data['password'];
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }
}
