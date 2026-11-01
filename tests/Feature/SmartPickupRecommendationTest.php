<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Equipment;
use App\Models\PickupPoint;
use App\Models\Rental;
use App\Models\User;
use App\Services\SmartPickupRecommendationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SmartPickupRecommendationTest extends TestCase
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

    public function test_nearest_eligible_point_ranks_first_with_normalized_explanation(): void
    {
        $rental = $this->rental();
        $near = $this->point(['name' => 'Proche', 'latitude' => 36.8, 'longitude' => 10.1]);
        $far = $this->point(['name' => 'Éloigné', 'latitude' => 35.8, 'longitude' => 10.1]);
        $this->weekdaySchedule($near, '09:00', '11:00');
        $this->weekdaySchedule($far, '09:00', '11:00');

        $ranked = $this->recommendations()->recommend(
            $rental,
            Carbon::parse('2026-11-02'),
            '09:30',
            36.8,
            10.1
        );

        $this->assertSame([$near->id, $far->id], array_column($ranked, 'pickup_point_id'));
        $this->assertGreaterThan($ranked[1]['score'], $ranked[0]['score']);
        $this->assertSame(100, $ranked[0]['score']);
        $this->assertGreaterThanOrEqual(0, $ranked[0]['score']);
        $this->assertLessThanOrEqual(100, $ranked[0]['score']);
        $this->assertArrayHasKey('distance', $ranked[0]['score_breakdown']);
        $this->assertContains('Plusieurs créneaux disponibles.', $ranked[0]['explanation']);
    }

    public function test_inactive_closed_weekday_and_exceptionally_closed_points_are_excluded(): void
    {
        $rental = $this->rental();
        $inactive = $this->point(['status' => 'inactive']);
        $closedWeekday = $this->point();
        $exceptionallyClosed = $this->point();
        $open = $this->point();

        $this->weekdaySchedule($inactive);
        $this->weekdaySchedule($closedWeekday, '09:00', '11:00', 3);
        $this->weekdaySchedule($exceptionallyClosed);
        $this->weekdaySchedule($open);
        $exceptionallyClosed->closures()->create([
            'closure_date' => '2026-11-02',
            'reason' => 'Fermeture exceptionnelle',
        ]);

        $recommendations = $this->recommendations()->recommend($rental, Carbon::parse('2026-11-02'));

        $this->assertSame([$open->id], array_column($recommendations, 'pickup_point_id'));
    }

    public function test_fully_booked_slots_and_lunch_break_are_not_recommended(): void
    {
        $rental = $this->rental();
        $fullPoint = $this->point(['capacity' => 1]);
        $lunchPoint = $this->point();
        $fullPoint->openingHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '09:30'],
        ]);
        $lunchPoint->openingHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '10:00'],
            ['day_of_week' => 1, 'opens_at' => '13:00', 'closes_at' => '14:00'],
        ]);
        $this->plannedDelivery($fullPoint, $this->rental(), '09:00');

        $recommendation = $this->recommendations()->recommend($rental, Carbon::parse('2026-11-02'));

        $this->assertSame([$lunchPoint->id], array_column($recommendation, 'pickup_point_id'));
        $starts = array_column($recommendation[0]['slots'], 'start');
        $this->assertSame(['09:00', '09:30', '13:00', '13:30'], $starts);
    }

    public function test_slot_duration_and_same_rental_existing_appointment_are_handled(): void
    {
        $rental = $this->rental();
        $point = $this->point(['slot_duration_minutes' => 60]);
        $this->weekdaySchedule($point, '09:00', '11:00');
        $this->plannedDelivery($point, $rental, '09:00');

        $recommendation = $this->recommendations()->recommend($rental, Carbon::parse('2026-11-02'));

        $this->assertSame(['09:00', '10:00'], array_column($recommendation[0]['slots'], 'start'));
        $this->assertSame(4, $recommendation[0]['remaining_capacity']);
        $this->assertArrayHasKey('capacity', $recommendation[0]['score_breakdown']);
        $this->assertArrayNotHasKey('distance', $recommendation[0]['score_breakdown']);
    }

    public function test_past_and_current_slots_are_not_recommended_for_today(): void
    {
        $rental = $this->rental();
        $rental->update(['start_date' => '2026-11-01']);
        $point = $this->point();
        $point->openingHours()->create([
            'day_of_week' => 7,
            'opens_at' => '09:00',
            'closes_at' => '11:00',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00:00'));

        $recommendation = $this->recommendations()->recommend($rental, Carbon::parse('2026-11-01'));

        $this->assertSame(['10:30'], array_column($recommendation[0]['slots'], 'start'));
    }

    public function test_missing_gps_redistributes_weights_and_ties_are_deterministic(): void
    {
        $rental = $this->rental();
        $first = $this->point(['latitude' => null, 'longitude' => null]);
        $second = $this->point(['latitude' => null, 'longitude' => null]);
        $this->weekdaySchedule($first);
        $this->weekdaySchedule($second);

        $recommendation = $this->recommendations()->recommend(
            $rental,
            Carbon::parse('2026-11-02'),
            null,
            36.8,
            10.1
        );

        $this->assertSame([$first->id, $second->id], array_column($recommendation, 'pickup_point_id'));
        $this->assertNull($recommendation[0]['distance_km']);
        $this->assertSame(['capacity'], array_keys($recommendation[0]['score_breakdown']));
        $this->assertSame(100, $recommendation[0]['score']);
    }

    public function test_invalid_coordinates_and_dates_outside_the_rental_are_rejected(): void
    {
        $rental = $this->rental();

        try {
            $this->recommendations()->recommend($rental, Carbon::parse('2026-11-02'), null, 91, 10);
            $this->fail('Invalid latitude should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('latitude', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $this->recommendations()->recommend($rental, Carbon::parse('2026-11-05'));
    }

    public function test_haversine_distance_uses_straight_line_distance_in_kilometers(): void
    {
        $distance = $this->recommendations()->distanceKilometers(36.8065, 10.1815, 36.8065, 10.1815);
        $oneDegree = $this->recommendations()->distanceKilometers(0, 0, 0, 1);

        $this->assertSame(0.0, $distance);
        $this->assertEqualsWithDelta(111.19, $oneDegree, 0.1);
    }

    private function recommendations(): SmartPickupRecommendationService
    {
        return app(SmartPickupRecommendationService::class);
    }

    private function point(array $overrides = []): PickupPoint
    {
        static $counter = 0;
        $counter++;

        return PickupPoint::create(array_merge([
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
    }

    private function weekdaySchedule(PickupPoint $point, string $opens = '09:00', string $closes = '11:00', int $day = 1): void
    {
        $point->openingHours()->create([
            'day_of_week' => $day,
            'opens_at' => $opens,
            'closes_at' => $closes,
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

    private function plannedDelivery(PickupPoint $point, Rental $rental, string $time): Delivery
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
