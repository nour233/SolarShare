<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Equipment;
use App\Models\PickupPoint;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartPickupExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-01 08:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_customer_can_search_select_confirm_and_see_smartpickup_booking(): void
    {
        $rental = $this->rental();
        $point = $this->point();

        $this->actingAs($rental->renter)
            ->get(route('rentals.smart-pickup', [
                'rental' => $rental,
                'date' => '2026-11-02',
                'preferred_time' => '09:30',
                'latitude' => '36.8',
                'longitude' => '10.1',
            ]))
            ->assertOk()
            ->assertSee('Trouvez le meilleur point de retrait')
            ->assertSee('Point de retrait')
            ->assertSee('09:30–10:00')
            ->assertSee('Recommandé');

        $selection = [
            'pickup_point_id' => $point->id,
            'date' => '2026-11-02',
            'scheduled_time' => '09:30',
            'preferred_time' => '09:30',
            'latitude' => '36.8',
            'longitude' => '10.1',
        ];
        $this->get(route('rentals.smart-pickup.confirm', ['rental' => $rental] + $selection))
            ->assertOk()
            ->assertSee('Vérifiez votre rendez-vous')
            ->assertSee('Confirmer le retrait');

        $this->post(route('rentals.smart-pickup.book', $rental), $selection + ['confirmed' => '1'])
            ->assertRedirect(route('rentals.show', $rental))
            ->assertSessionHas('status', 'Votre rendez-vous de retrait est confirmé.');

        $this->assertDatabaseCount('deliveries', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($rental->renter)
            ->get(route('rentals.show', $rental))
            ->assertOk()
            ->assertSee('Trouver un meilleur créneau')
            ->assertSee($point->name)
            ->assertSee('09:30');
    }

    public function test_customer_smartpickup_routes_prevent_idor_and_require_authentication(): void
    {
        $ownerRental = $this->rental();
        $otherRental = $this->rental();

        $this->get(route('rentals.smart-pickup', $ownerRental))
            ->assertRedirect(route('login'));

        $this->actingAs($otherRental->renter)
            ->get(route('rentals.smart-pickup', $ownerRental))
            ->assertForbidden();

        $this->actingAs($otherRental->renter)
            ->post(route('rentals.smart-pickup.book', $ownerRental), [
                'pickup_point_id' => $this->point()->id,
                'date' => '2026-11-02',
                'scheduled_time' => '09:00',
                'confirmed' => '1',
            ])
            ->assertForbidden();
    }

    public function test_customer_input_validation_and_stale_slot_confirmation_are_rejected(): void
    {
        $rental = $this->rental();
        $point = $this->point(['capacity' => 1]);
        $this->actingAs($rental->renter);

        $this->get(route('rentals.smart-pickup', [
            'rental' => $rental,
            'date' => '2026-11-02',
            'latitude' => 91,
            'longitude' => 10,
        ]))->assertSessionHasErrors('latitude');

        $other = $this->rental();
        Delivery::create([
            'type' => 'pickup',
            'scheduled_date' => '2026-11-02',
            'scheduled_time' => '09:00',
            'status' => 'planned',
            'delivery_fee' => 0,
            'rental_id' => $other->id,
            'pickup_point_id' => $point->id,
        ]);

        $this->get(route('rentals.smart-pickup.confirm', [
            'rental' => $rental,
            'pickup_point_id' => $point->id,
            'date' => '2026-11-02',
            'scheduled_time' => '09:00',
        ]))->assertSessionHasErrors('scheduled_time');

        $this->post(route('rentals.smart-pickup.book', $rental), [
            'pickup_point_id' => $point->id,
            'date' => '2026-11-02',
            'scheduled_time' => '09:00',
            'confirmed' => '1',
        ])->assertSessionHasErrors('scheduled_time');

        $this->assertDatabaseCount('deliveries', 1);
    }

    public function test_admin_can_use_same_recommendation_and_booking_flow(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $rental = $this->rental();
        $point = $this->point();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.deliveries.smart-pickup'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.deliveries.smart-pickup', [
                'rental_id' => $rental->id,
                'date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertSee('SmartPickup')
            ->assertSee($point->name);

        $selection = [
            'rental_id' => $rental->id,
            'pickup_point_id' => $point->id,
            'date' => '2026-11-02',
            'scheduled_time' => '09:00',
        ];
        $this->get(route('admin.deliveries.smart-pickup.confirm', $selection))
            ->assertOk()
            ->assertSee('Vérifiez votre rendez-vous');

        $this->post(route('admin.deliveries.smart-pickup.book'), $selection + ['confirmed' => '1'])
            ->assertRedirect(route('admin.deliveries.show', Delivery::firstOrFail()))
            ->assertSessionHas('success', 'Rendez-vous de retrait planifié.');

        $this->assertDatabaseHas('deliveries', [
            'rental_id' => $rental->id,
            'pickup_point_id' => $point->id,
            'scheduled_time' => '09:00',
        ]);
    }

    private function rental(): Rental
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Catégorie '.uniqid(), 'icon' => 'fa-solar-panel']);
        $equipment = Equipment::create([
            'title' => 'Panneau de démonstration',
            'description' => 'Test',
            'condition' => 'Bon état',
            'price_per_day' => 10,
            'deposit' => 0,
            'category_id' => $category->id,
            'owner_id' => $owner->id,
        ]);

        return Rental::create([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'total_price' => 30,
            'status' => 'accepted',
            'equipment_id' => $equipment->id,
            'renter_id' => $renter->id,
        ]);
    }

    private function point(array $overrides = []): PickupPoint
    {
        static $counter = 0;
        $counter++;
        $point = PickupPoint::create(array_merge([
            'name' => 'SmartPickup '.$counter,
            'address' => '10 rue solaire',
            'city' => 'Tunis',
            'latitude' => 36.8,
            'longitude' => 10.1,
            'opening_hours' => 'Legacy',
            'capacity' => 2,
            'slot_duration_minutes' => 30,
            'status' => 'active',
        ], $overrides));
        $point->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '10:00',
        ]);

        return $point;
    }
}
