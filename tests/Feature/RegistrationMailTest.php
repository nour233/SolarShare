<?php
namespace Tests\Feature;

use App\Notifications\RegistrationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationMailTest extends TestCase
{
    use RefreshDatabase;

    private function register(): void
    {
        $this->post('/register', ['name' => 'Nour', 'email' => 'nour@example.com', 'password' => 'SolarShare123!', 'password_confirmation' => 'SolarShare123!'])->assertRedirect(route('register.verify'));
    }

    private function code(): string
    {
        return Notification::sent(new \Illuminate\Notifications\AnonymousNotifiable, RegistrationCode::class)->last()->code;
    }

    public function test_account_is_created_only_after_correct_code(): void
    {
        Notification::fake();
        $this->register();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        Notification::assertSentOnDemand(RegistrationCode::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'nour@example.com');
        $code = $this->code();
        $this->get('/register/verify')->assertOk();
        $this->post('/register/verify', ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertNotNull(\App\Models\User::first()->email_verified_at);
        $this->post('/register/verify', ['code' => $code])->assertRedirect('/register');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_incorrect_and_expired_codes_do_not_create_account(): void
    {
        Notification::fake();
        $this->register();
        $wrong = $this->code() === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register/verify', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/register/verify', ['code' => $this->code()])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_resend_replaces_code_and_expiration_is_enforced(): void
    {
        Notification::fake();
        $this->register();
        $oldHash = session('registration.code_hash');
        $this->post('/register/resend')->assertSessionHasErrors('code');
        $this->travel(11)->minutes();
        $this->post('/register/verify', ['code' => $this->code()])->assertSessionHasErrors('code');
        $this->post('/register/resend')->assertRedirect(route('register.verify'));
        $this->assertNotSame($oldHash, session('registration.code_hash'));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_delivery_failure_does_not_create_or_authenticate_account(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->post('/register', ['name' => 'Nour', 'email' => 'nour@example.com', 'password' => 'SolarShare123!', 'password_confirmation' => 'SolarShare123!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(session('registration'));
    }
}
