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

    public function test_saved_logistics_preferences_prefill_and_personalize_smartpickup(): void
    {
        $rental = $this->rental();
        $point = $this->point(['name' => 'Mon point favori']);
        $rental->renter->logisticsPreference()->create([
            'preferred_pickup_point_id' => $point->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '10:00',
            'preferred_city' => 'Tunis',
        ]);

        $this->actingAs($rental->renter)
            ->get(route('rentals.smart-pickup', [
                'rental' => $rental,
                'date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertSee('Vos préférences ont été prises en compte.')
            ->assertSee('Mon point favori')
            ->assertSee('Un créneau réservable est disponible pour cette date.')
            ->assertSee('id="preferred_start_time" type="time" name="preferred_start_time" value="09:00"', false)
            ->assertSee('id="preferred_end_time" type="time" name="preferred_end_time" value="10:00"', false)
            ->assertSee('name="preferred_city" maxlength="100" value="Tunis"', false)
            ->assertSee('Votre point de retrait favori est disponible et a été favorisé dans le classement.');
    }

    public function test_unavailable_favorite_is_explained_and_personalized_booking_revalidates(): void
    {
        $rental = $this->rental();
        $favorite = $this->point(['name' => 'Favori fermé']);
        $alternative = $this->point(['name' => 'Alternative ouverte']);
        $rental->renter->logisticsPreference()->create([
            'preferred_pickup_point_id' => $favorite->id,
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '10:00',
            'preferred_city' => 'Tunis',
        ]);
        $favorite->closures()->create([
            'closure_date' => '2026-11-02',
            'reason' => 'Fermeture exceptionnelle',
        ]);

        $this->actingAs($rental->renter)
            ->get(route('rentals.smart-pickup', [
                'rental' => $rental,
                'date' => '2026-11-02',
            ]))
            ->assertOk()
            ->assertSee('Favori fermé')
            ->assertSee('Ce point n’a pas de créneau réservable parmi les résultats pour cette date.')
            ->assertSee('Alternative ouverte.');

        $selection = [
            'pickup_point_id' => $alternative->id,
            'date' => '2026-11-02',
            'scheduled_time' => '09:00',
            'favorite_pickup_point_id' => $favorite->id,
            'preferred_city' => 'Tunis',
            'preferred_start_time' => '09:00',
            'preferred_end_time' => '10:00',
        ];
        $this->get(route('rentals.smart-pickup.confirm', ['rental' => $rental] + $selection))
            ->assertOk()
            ->assertSee('Vérifiez votre rendez-vous');

        $alternative->closures()->create([
            'closure_date' => '2026-11-02',
            'reason' => 'Fermeture après affichage de la confirmation',
        ]);
        $this->post(route('rentals.smart-pickup.book', $rental), $selection + ['confirmed' => '1'])
            ->assertSessionHasErrors('scheduled_time');
        $this->assertDatabaseCount('deliveries', 0);
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
