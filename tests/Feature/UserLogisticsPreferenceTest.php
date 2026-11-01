<?php

namespace Tests\Feature;

use App\Models\PickupPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLogisticsPreferenceTest extends TestCase
{
    use RefreshDatabase;

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
}
