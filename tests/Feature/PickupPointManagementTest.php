<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Equipment;
use App\Models\PickupPoint;
use App\Models\PickupPointClosure;
use App\Models\Rental;
use App\Models\User;
use App\Services\PickupPointOperationsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupPointManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_hours_respect_closed_days_intervals_and_exceptional_closures(): void
    {
        $point = $this->pickupPoint();
        $point->openingHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '12:00'],
            ['day_of_week' => 1, 'opens_at' => '13:00', 'closes_at' => '17:00'],
        ]);
        $operations = app(PickupPointOperationsService::class);

        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-03 10:00')));
        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-02 08:59')));
        $this->assertTrue($operations->isOpenAt($point, Carbon::parse('2026-11-02 09:00')));
        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-02 12:00')));
        $this->assertTrue($operations->isOpenAt($point, Carbon::parse('2026-11-02 13:30')));

        $point->closures()->create([
            'closure_date' => '2026-11-02',
            'reason' => 'Jour férié',
        ]);
        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-02 10:00')));
        $capacity = $operations->utilizationForDate(
            collect([$point->load('openingHours')]),
            Carbon::parse('2026-11-02')
        )[$point->id];
        $this->assertTrue($capacity['closed_for_date']);
        $this->assertSame([], $capacity['slots']);
    }

    public function test_empty_schedule_and_inactive_points_are_not_open(): void
    {
        $point = $this->pickupPoint();
        $operations = app(PickupPointOperationsService::class);

        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-02 10:00')));

        $point->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ]);
        $point->update(['status' => 'inactive']);

        $this->assertFalse($operations->isOpenAt($point, Carbon::parse('2026-11-02 10:00')));
        $point->load('openingHours');
        $metrics = $operations->utilizationForDate(collect([$point]), Carbon::parse('2026-11-02'))[$point->id];
        $this->assertSame(0, $metrics['available_capacity']);
        $this->assertSame('inactive', $metrics['status']);
        $this->assertSame([], $metrics['slots']);
    }

    public function test_capacity_is_calculated_per_slot_and_ignores_ineligible_or_duplicate_deliveries(): void
    {
        $point = $this->pickupPoint(['capacity' => 2]);
        $point->openingHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '10:00'],
            ['day_of_week' => 1, 'opens_at' => '11:00', 'closes_at' => '12:00'],
        ]);

        $firstRental = $this->rental();
        $secondRental = $this->rental();
        $completedRental = $this->rental();
        $returnedRental = $this->rental();
        $inTransitRental = $this->rental();
        $homeDeliveryRental = $this->rental();

        $this->delivery($point, $firstRental, '09:05');
        $this->delivery($point, $firstRental, '09:20');
        $this->delivery($point, $secondRental, '09:25');
        $this->delivery($point, $completedRental, '09:30', 'delivered');
        $this->delivery($point, $returnedRental, '09:32', 'returned');
        $this->delivery($point, $inTransitRental, '09:35', 'in_transit');
        $this->delivery($point, $homeDeliveryRental, '09:40', 'planned', 'home_delivery');

        $metrics = app(PickupPointOperationsService::class)
            ->utilizationForDate(collect([$point->load('openingHours')]), Carbon::parse('2026-11-02'))[$point->id];

        $this->assertSame(2, $metrics['reservation_count']);
        $this->assertSame(2, $metrics['slotted_reservations']);
        $this->assertSame(6, $metrics['available_capacity']);
        $this->assertSame(8, $metrics['total_slot_capacity']);
        $this->assertSame(25, $metrics['occupancy_percent']);
        $this->assertSame('full', $metrics['slots'][0]['status']);
        $this->assertSame(1, $metrics['full_slots']);
        $this->assertSame(0, $metrics['unslotted_reservations']);
    }

    public function test_capacity_dashboard_filters_by_date_and_renders_slot_utilization(): void
    {
        $admin = $this->admin();
        $point = $this->pickupPoint(['capacity' => 3, 'slot_duration_minutes' => 60]);
        $point->openingHours()->createMany([
            ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '11:00'],
            ['day_of_week' => 2, 'opens_at' => '13:00', 'closes_at' => '15:00'],
        ]);
        $mondayRental = $this->rental();
        $tuesdayRental = $this->rental();
        $this->delivery($point, $mondayRental, '09:30');
        Delivery::create([
            'type' => 'pickup',
            'scheduled_date' => '2026-11-03',
            'scheduled_time' => '13:30',
            'status' => 'planned',
            'delivery_fee' => 0,
            'rental_id' => $tuesdayRental->id,
            'pickup_point_id' => $point->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pickup-points.capacity', ['date' => '2026-11-02']))
            ->assertOk()
            ->assertSee('2026-11-02')
            ->assertSee('09:00–10:00 · 1/3')
            ->assertDontSee('13:00–14:00 · 1/3');

        $this->get(route('admin.pickup-points.capacity', ['date' => '2026-11-03']))
            ->assertOk()
            ->assertSee('13:00–14:00 · 1/3')
            ->assertDontSee('09:00–10:00 · 1/3');
    }

    public function test_admin_schedule_validation_rejects_overlapping_intervals_and_accepts_closed_days(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.pickup-points.create'))
            ->assertOk()
            ->assertSee('Planning hebdomadaire');
        $schedule = $this->closedWeeklySchedule();
        $schedule[1] = [
            'closed' => '0',
            'intervals' => [
                ['opens_at' => '09:00', 'closes_at' => '11:00'],
                ['opens_at' => '10:00', 'closes_at' => '12:00'],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.pickup-points.store'), $this->pickupPointData($schedule))
            ->assertSessionHasErrors('weekly_schedule.1.intervals.1.opens_at');

        $schedule[1] = [
            'closed' => '0',
            'intervals' => [
                ['opens_at' => '13:00', 'closes_at' => '12:00'],
            ],
        ];
        $this->post(route('admin.pickup-points.store'), $this->pickupPointData($schedule))
            ->assertSessionHasErrors('weekly_schedule.1.intervals.0.closes_at');

        $schedule[1] = [
            'closed' => '0',
            'intervals' => [
                ['opens_at' => '09:00', 'closes_at' => '12:00'],
                ['opens_at' => '13:00', 'closes_at' => '17:00'],
            ],
        ];

        $this->post(route('admin.pickup-points.store'), $this->pickupPointData($schedule))
            ->assertRedirect(route('admin.pickup-points.index'));

        $point = PickupPoint::firstOrFail();
        $this->assertDatabaseCount('pickup_point_opening_hours', 2);
        $this->assertSame(30, $point->slot_duration_minutes);
        $this->assertDatabaseHas('pickup_point_opening_hours', [
            'pickup_point_id' => $point->id,
            'day_of_week' => 1,
        ]);
        $this->assertDatabaseMissing('pickup_point_opening_hours', [
            'pickup_point_id' => $point->id,
            'day_of_week' => 2,
        ]);
    }

    public function test_existing_crud_keeps_legacy_opening_hours_and_preserves_schedule_when_omitted(): void
    {
        $admin = $this->admin();
        $pointData = $this->pickupPointData();
        $this->actingAs($admin)
            ->post(route('admin.pickup-points.store'), $pointData)
            ->assertRedirect(route('admin.pickup-points.index'));

        $point = PickupPoint::firstOrFail();
        $point->openingHours()->create([
            'day_of_week' => 1,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ]);

        $this->put(route('admin.pickup-points.update', $point), array_merge($pointData, ['name' => 'Point modifié']))
            ->assertRedirect(route('admin.pickup-points.index'));

        $this->assertDatabaseHas('pickup_points', [
            'id' => $point->id,
            'name' => 'Point modifié',
            'opening_hours' => 'Lun-Sam 08:00 - 18:00',
        ]);
        $this->assertDatabaseHas('pickup_point_opening_hours', [
            'pickup_point_id' => $point->id,
            'day_of_week' => 1,
            'opens_at' => '09:00',
        ]);

        $this->get(route('admin.pickup-points.index'))->assertOk()->assertSee('Point modifié');
        $this->get(route('admin.pickup-points.edit', $point))->assertOk()->assertSee('Planning hebdomadaire');
        $this->get(route('admin.pickup-points.show', $point))->assertOk()->assertSee('Point modifié');
        $this->delete(route('admin.pickup-points.destroy', $point))
            ->assertRedirect(route('admin.pickup-points.index'));
        $this->assertDatabaseMissing('pickup_points', ['id' => $point->id]);
        $this->assertDatabaseMissing('pickup_point_opening_hours', ['pickup_point_id' => $point->id]);
    }

    public function test_admin_can_manage_closures_and_only_admins_can_open_capacity_pages(): void
    {
        $point = $this->pickupPoint();
        $this->get(route('admin.pickup-points.capacity'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.pickup-points.capacity'))->assertForbidden();
        $this->get(route('admin.pickup-points.index'))->assertForbidden();

        $admin = $this->admin();
        $closureDate = today()->addDays(3)->format('Y-m-d');
        $this->actingAs($admin)
            ->get(route('admin.pickup-points.capacity', ['date' => $closureDate]))
            ->assertOk()
            ->assertSee('Capacité des points de retrait');

        $this->post(route('admin.pickup-points.closures.store', $point), [
            'closure_date' => $closureDate,
            'reason' => 'Maintenance',
        ])->assertRedirect(route('admin.pickup-points.show', $point));
        $this->assertDatabaseCount('pickup_point_closures', 1);
        $this->post(route('admin.pickup-points.closures.store', $point), [
            'closure_date' => $closureDate,
            'reason' => 'Doublon',
        ])->assertSessionHasErrors('closure_date');
        $this->assertDatabaseCount('pickup_point_closures', 1);
        $this->get(route('admin.pickup-points.show', $point))
            ->assertOk()
            ->assertSee('Maintenance');
        $closure = PickupPointClosure::firstOrFail();

        $this->put(route('admin.pickup-points.closures.update', [$point, $closure]), [
            'closure_date' => $closureDate,
            'reason' => 'Maintenance annuelle',
        ])->assertRedirect(route('admin.pickup-points.show', $point));
        $this->assertDatabaseHas('pickup_point_closures', [
            'id' => $closure->id,
            'reason' => 'Maintenance annuelle',
        ]);

        $this->delete(route('admin.pickup-points.closures.destroy', [$point, $closure]))
            ->assertRedirect(route('admin.pickup-points.show', $point));
        $this->assertDatabaseMissing('pickup_point_closures', ['id' => $closure->id]);
    }

    public function test_pickup_point_details_show_schedule_closure_capacity_and_gps(): void
    {
        $admin = $this->admin();
        $point = $this->pickupPoint(['capacity' => 3, 'slot_duration_minutes' => 30]);
        $date = today()->addDays(1);
        $point->openingHours()->create([
            'day_of_week' => $date->isoWeekday(),
            'opens_at' => '09:00',
            'closes_at' => '10:00',
        ]);
        $point->closures()->create([
            'closure_date' => $date->format('Y-m-d'),
            'reason' => 'Maintenance planifiée',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pickup-points.show', [
                'pickup_point' => $point,
                'date' => $date->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Point test')
            ->assertSee('09:00–10:00')
            ->assertSee('Maintenance planifiée')
            ->assertSee('Ce point est exceptionnellement fermé')
            ->assertSee('36.8065')
            ->assertSee('10.1815');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function pickupPoint(array $overrides = []): PickupPoint
    {
        return PickupPoint::create(array_merge([
            'name' => 'Point test',
            'address' => '10 rue solaire',
            'city' => 'Tunis',
            'latitude' => 36.8065,
            'longitude' => 10.1815,
            'opening_hours' => 'Lun-Sam 08:00 - 18:00',
            'capacity' => 4,
            'status' => 'active',
        ], $overrides));
    }

    private function pickupPointData(?array $schedule = null): array
    {
        $data = [
            'name' => 'Point test',
            'address' => '10 rue solaire',
            'city' => 'Tunis',
            'latitude' => '36.8065',
            'longitude' => '10.1815',
            'opening_hours' => 'Lun-Sam 08:00 - 18:00',
            'capacity' => 4,
            'status' => 'active',
        ];

        if ($schedule !== null) {
            $data['weekly_schedule'] = $schedule;
        }

        return $data;
    }

    private function closedWeeklySchedule(): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(fn (int $day) => [$day => ['closed' => '1']])
            ->all();
    }

    private function rental(): Rental
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create([
            'name' => 'Solar '.uniqid(),
            'icon' => 'fa-solar-panel',
        ]);
        $equipment = Equipment::create([
            'title' => 'Panneau solaire',
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

    private function delivery(
        PickupPoint $point,
        Rental $rental,
        string $time,
        string $status = 'planned',
        string $type = 'pickup'
    ): Delivery {
        return Delivery::create([
            'type' => $type,
            'scheduled_date' => '2026-11-02',
            'scheduled_time' => $time,
            'status' => $status,
            'delivery_fee' => 0,
            'rental_id' => $rental->id,
            'pickup_point_id' => $point->id,
        ]);
    }
}
