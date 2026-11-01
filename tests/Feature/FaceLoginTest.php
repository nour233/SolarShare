<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_enroll_a_face_descriptor(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $descriptor = array_fill(0, 128, 0.1);

        $this->actingAs($user)->postJson('/profile/face-login', [
            'current_password' => 'password',
            'descriptor' => $descriptor,
        ])->assertOk();

        $this->assertNotNull($user->fresh()->face_descriptor);
    }

    public function test_matching_face_descriptor_can_login(): void
    {
        $user = User::factory()->create(['email' => 'face@example.test']);
        $descriptor = array_fill(0, 128, 0.1);
        $user->forceFill(['face_descriptor' => $descriptor])->save();

        $this->postJson('/login/face', [
            'descriptor' => $descriptor,
        ])->assertOk()->assertJsonPath('redirect', route('login.face.password'));

        $this->post('/login/face/password', ['password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }
}
