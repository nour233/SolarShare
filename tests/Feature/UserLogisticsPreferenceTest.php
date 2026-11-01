<?php

namespace Tests\Feature;

use App\Models\PickupPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLogisticsPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_save_update_and_reset_logistics_preferences(): void
    {
        $user = User::factory()->create();
        $point = $this->pickupPoint();
        $this->actingAs($user);

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_pickup_point_id' => $point->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '12:00',
            'preferred_city' => 'Tunis',
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Vos préférences de retrait ont été enregistrées.');

        $this->assertDatabaseHas('user_logistics_preferences', [
            'user_id' => $user->id,
            'preferred_pickup_point_id' => $point->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '12:00',
            'preferred_city' => 'Tunis',
        ]);

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_pickup_point_id' => null,
            'preferred_start_time' => null,
            'preferred_end_time' => null,
            'preferred_city' => 'Sousse',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('user_logistics_preferences', 1);
        $this->assertDatabaseHas('user_logistics_preferences', [
            'user_id' => $user->id,
            'preferred_pickup_point_id' => null,
            'preferred_start_time' => null,
            'preferred_end_time' => null,
            'preferred_city' => 'Sousse',
        ]);

        $this->delete(route('profile.logistics-preferences.reset'))
            ->assertSessionHas('status', 'Vos préférences de retrait ont été réinitialisées.');

        $this->assertDatabaseMissing('user_logistics_preferences', ['user_id' => $user->id]);
    }

    public function test_preference_endpoints_require_authentication_and_ignore_another_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $point = $this->pickupPoint();

        $this->put(route('profile.logistics-preferences.update'), ['preferred_city' => 'Tunis'])
            ->assertRedirect(route('login'));
        $this->delete(route('profile.logistics-preferences.reset'))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->put(route('profile.logistics-preferences.update'), [
                'user_id' => $otherUser->id,
                'preferred_pickup_point_id' => $point->id,
                'preferred_city' => 'Tunis',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_logistics_preferences', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_logistics_preferences', ['user_id' => $otherUser->id]);
    }

    public function test_invalid_pickup_point_time_range_and_city_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_pickup_point_id' => 99999,
        ])->assertSessionHasErrorsIn('logisticsPreferences', 'preferred_pickup_point_id');

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_start_time' => '14:00',
            'preferred_end_time' => '09:00',
        ])->assertSessionHasErrorsIn('logisticsPreferences', 'preferred_end_time');

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_start_time' => '09:00',
        ])->assertSessionHasErrorsIn('logisticsPreferences', 'preferred_end_time');

        $this->put(route('profile.logistics-preferences.update'), [
            'preferred_city' => str_repeat('a', 101),
        ])->assertSessionHasErrorsIn('logisticsPreferences', 'preferred_city');
    }

    public function test_profile_displays_saved_logistics_preferences(): void
    {
        $user = User::factory()->create();
        $point = $this->pickupPoint();
        $user->logisticsPreference()->create([
            'preferred_pickup_point_id' => $point->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '12:00',
            'preferred_city' => 'Tunis',
        ]);

        $this->actingAs($user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Mes préférences de retrait')
            ->assertSee($point->name)
            ->assertSee('09:00')
            ->assertSee('12:00')
            ->assertSee('Tunis');
    }

    public function test_user_has_one_logistics_preference_and_pickup_point_deletion_clears_the_reference(): void
    {
        $user = User::factory()->create();
        $point = PickupPoint::create([
            'name' => 'Preference point',
            'address' => '1 Solar Street',
            'city' => 'Tunis',
            'latitude' => 36.8,
            'longitude' => 10.1,
            'opening_hours' => 'Legacy',
            'capacity' => 2,
            'slot_duration_minutes' => 30,
            'status' => 'active',
        ]);
        $preference = $user->logisticsPreference()->create([
            'preferred_pickup_point_id' => $point->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '12:00',
            'preferred_city' => 'Tunis',
        ]);

        $this->assertSame($preference->id, $user->fresh()->logisticsPreference->id);
        $this->assertSame($point->id, $preference->preferredPickupPoint->id);

        $point->delete();

        $this->assertNull($preference->fresh()->preferred_pickup_point_id);
    }

    public function test_deleting_a_user_cascades_their_logistics_preference(): void
    {
        $user = User::factory()->create();
        $preference = $user->logisticsPreference()->create(['preferred_city' => 'Tunis']);

        $user->delete();

        $this->assertDatabaseMissing('user_logistics_preferences', ['id' => $preference->id]);
    }

    private function pickupPoint(): PickupPoint
    {
        return PickupPoint::create([
            'name' => 'Profile preference point',
            'address' => '1 Solar Street',
            'city' => 'Tunis',
            'latitude' => 36.8,
            'longitude' => 10.1,
            'opening_hours' => 'Legacy',
            'capacity' => 2,
            'slot_duration_minutes' => 30,
            'status' => 'active',
        ]);
    }
}
