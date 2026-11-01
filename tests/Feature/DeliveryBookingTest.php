<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Equipment;
use App\Models\PickupPoint;
use App\Models\Rental;
use App\Models\User;
use App\Services\DeliveryBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryBookingTest extends TestCase
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

    public function test_customer_can_book_own_rental_and_notification_and_capacity_are_updated(): void
    {
        $rental = $this->rental();
        $point = $this->point();

        $result = app(DeliveryBookingService::class)->bookPickup(
            $rental,
            $point->id,
            '2026-11-02',
            '09:00',
            $rental->renter
        );

        $this->assertTrue($result['changed']);
        $this->assertSame('planned', $result['delivery']->status);
        $this->assertSame($rental->id, $result['delivery']->rental_id);
        $this->assertSame($point->id, $result['delivery']->pickup_point_id);
        $this->assertDatabaseCount('notifications', 1);
        $notification = DB::table('notifications')
            ->where('notifiable_id', $rental->renter_id)
            ->first();
        $this->assertNotNull($notification);
        $this->assertSame('pickup_booking', json_decode($notification->data, true)['type']);

        $dashboard = app(\App\Services\PickupPointOperationsService::class)
            ->utilizationForDate(collect([$point->load('openingHours')]), Carbon::parse('2026-11-02'))[$point->id];
        $this->assertSame(1, $dashboard['reservation_count']);
        $this->assertSame(1, $dashboard['slots'][0]['reserved']);
    }

    public function test_repeated_confirmation_is_idempotent_and_planned_pickup_can_be_rescheduled(): void
    {
        $rental = $this->rental();
        $firstPoint = $this->point();
        $secondPoint = $this->point();
        $booking = app(DeliveryBookingService::class);
        $first = $booking->bookPickup($rental, $firstPoint->id, '2026-11-02', '09:00', $rental->renter);
        $same = $booking->bookPickup($rental, $firstPoint->id, '2026-11-02', '09:00', $rental->renter);
        $rescheduled = $booking->bookPickup($rental, $secondPoint->id, '2026-11-02', '09:00', $rental->renter);

        $this->assertTrue($first['changed']);
        $this->assertFalse($same['changed']);
        $this->assertSame($first['delivery']->id, $rescheduled['delivery']->id);
        $this->assertSame($secondPoint->id, $rescheduled['delivery']->fresh()->pickup_point_id);
        $this->assertDatabaseCount('deliveries', 1);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_booking_rejects_full_closed_past_and_out_of_period_slots(): void
    {
        $rental = $this->rental();
        $point = $this->point(['capacity' => 1]);
        $otherRental = $this->rental();
        $this->delivery($point, $otherRental, '09:00');
        $booking = app(DeliveryBookingService::class);

        try {
            $booking->bookPickup($rental, $point->id, '2026-11-02', '09:00', $rental->renter);
            $this->fail('A full slot must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_time', $exception->errors());
        }

        $point->closures()->create(['closure_date' => '2026-11-03', 'reason' => 'Fermé']);
        try {
            $booking->bookPickup($rental, $point->id, '2026-11-03', '09:00', $rental->renter);
            $this->fail('Exceptional closures must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_time', $exception->errors());
        }

        try {
            $booking->bookPickup($rental, $point->id, '2026-11-01', '09:00', $rental->renter);
            $this->fail('Past dates must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date', $exception->errors());
        }

        try {
            $booking->bookPickup($rental, $point->id, '2026-11-05', '09:00', $rental->renter);
            $this->fail('Dates outside rental period must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('date', $exception->errors());
        }
    }

    public function test_home_deliveries_are_not_silently_converted_and_completed_delivery_is_preserved(): void
    {
        $rental = $this->rental();
        $point = $this->point();
        $homeDelivery = Delivery::create([
            'type' => 'home_delivery',
            'scheduled_date' => '2026-11-02',
            'scheduled_time' => '09:00',
            'status' => 'planned',
            'delivery_fee' => 0,
            'rental_id' => $rental->id,
        ]);

        try {
            app(DeliveryBookingService::class)->bookPickup(
                $rental,
                $point->id,
                '2026-11-02',
                '09:00',
                $rental->renter
            );
            $this->fail('Existing home delivery must not be silently converted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delivery', $exception->errors());
        }

        $homeDelivery->update(['status' => 'delivered']);
        try {
            app(DeliveryBookingService::class)->bookPickup(
                $rental,
                $point->id,
                '2026-11-02',
                '09:00',
                $rental->renter
            );
            $this->fail('Completed delivery must be preserved and block replacement.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delivery', $exception->errors());
        }

        $this->assertSame('delivered', $homeDelivery->fresh()->status);
        $this->assertSame('home_delivery', $homeDelivery->fresh()->type);
        $this->assertDatabaseCount('deliveries', 1);
    }

    public function test_customer_delivery_crud_routes_use_capacity_validation(): void
    {
        $rental = $this->rental();
        $point = $this->point();

        $this->actingAs($rental->renter)
            ->post(route('deliveries.store', $rental), [
                'type' => 'pickup',
                'pickup_point_id' => $point->id,
                'scheduled_date' => '2026-11-02',
                'scheduled_time' => '09:00',
            ])
            ->assertRedirect(route('rentals.show', $rental));

        $this->assertDatabaseHas('deliveries', [
            'rental_id' => $rental->id,
            'pickup_point_id' => $point->id,
            'type' => 'pickup',
            'status' => 'planned',
        ]);

        $foreignRental = $this->rental();
        $this->actingAs($rental->renter)
            ->post(route('deliveries.store', $foreignRental), [
                'type' => 'pickup',
                'pickup_point_id' => $point->id,
                'scheduled_date' => '2026-11-02',
                'scheduled_time' => '09:30',
            ])
            ->assertForbidden();
    }

    public function test_home_delivery_crud_remains_available(): void
    {
        $rental = $this->rental();

        $this->actingAs($rental->renter)
            ->post(route('deliveries.store', $rental), [
                'type' => 'home_delivery',
                'scheduled_date' => '2026-11-02',
                'scheduled_time' => '09:00',
            ])
            ->assertRedirect(route('rentals.show', $rental));

        $this->assertDatabaseHas('deliveries', [
            'rental_id' => $rental->id,
            'type' => 'home_delivery',
            'status' => 'planned',
            'pickup_point_id' => null,
        ]);
    }

    private function rental(): Rental
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Catégorie '.uniqid(), 'icon' => 'fa-solar-panel']);
        $equipment = Equipment::create([
            'title' => 'Équipement solaire',
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
            'name' => 'Point '.$counter,
            'address' => 'Adresse '.$counter,
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
            'closes_at' => '11:00',
        ]);

        return $point;
    }

    private function delivery(PickupPoint $point, Rental $rental, string $time): Delivery
    {
        return Delivery::create([
            'type' => 'pickup',
            'scheduled_date' => '2026-11-02',
            'scheduled_time' => $time,
            'status' => 'planned',
            'delivery_fee' => 0,
            'rental_id' => $rental->id,
            'pickup_point_id' => $point->id,
        ]);
    }
}
