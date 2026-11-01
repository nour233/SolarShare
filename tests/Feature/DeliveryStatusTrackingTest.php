<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Equipment;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\DeliveryStatusUpdated;
use App\Services\DeliveryStatusService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryStatusTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_follow_allowed_transitions_with_history_and_renter_notifications(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery();

        foreach ([
            'in_transit' => ['planned', 'in_transit'],
            'delivered' => ['in_transit', 'delivered'],
            'returned' => ['delivered', 'returned'],
        ] as $status => [$previous, $next]) {
            $this->actingAs($admin)
                ->patch(route('admin.deliveries.status', $delivery), [
                    'status' => $status,
                    'comment' => 'Mise à jour de démonstration',
                ])
                ->assertRedirect(route('admin.deliveries.show', $delivery));

            $this->assertDatabaseHas('delivery_status_histories', [
                'delivery_id' => $delivery->id,
                'previous_status' => $previous,
                'new_status' => $next,
                'user_id' => $admin->id,
                'comment' => 'Mise à jour de démonstration',
            ]);
        }

        $this->assertSame('returned', $delivery->fresh()->status);
        $this->assertDatabaseCount('delivery_status_histories', 3);
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $delivery->rental->renter_id,
            'type' => DeliveryStatusUpdated::class,
        ]);
    }

    public function test_invalid_transition_is_rejected_without_changing_delivery_or_history(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery();

        $this->actingAs($admin)
            ->patch(route('admin.deliveries.status', $delivery), [
                'status' => 'returned',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('planned', $delivery->fresh()->status);
        $this->assertDatabaseCount('delivery_status_histories', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_repeating_the_current_status_does_not_create_history_or_notification(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery();

        $this->actingAs($admin)
            ->patch(route('admin.deliveries.status', $delivery), [
                'status' => 'planned',
            ])
            ->assertRedirect(route('admin.deliveries.show', $delivery))
            ->assertSessionHas('success', 'Cette livraison possède déjà ce statut.');

        $this->assertDatabaseCount('delivery_status_histories', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_delivery_and_history_roll_back_together_if_history_write_fails(): void
    {
        $delivery = $this->delivery();
        DB::statement(
            "CREATE TRIGGER fail_delivery_history BEFORE INSERT ON delivery_status_histories
             BEGIN SELECT RAISE(FAIL, 'simulated history failure'); END"
        );

        try {
            app(DeliveryStatusService::class)->transition(
                $delivery,
                'in_transit',
                $this->admin()
            );
            $this->fail('The simulated history failure should be raised.');
        } catch (QueryException) {
            $this->assertSame('planned', $delivery->fresh()->status);
            $this->assertDatabaseCount('delivery_status_histories', 0);
            $this->assertDatabaseCount('notifications', 0);
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS fail_delivery_history');
        }
    }

    public function test_status_changes_require_admin_authorization(): void
    {
        $delivery = $this->delivery();
        $this->patch(route('admin.deliveries.status', $delivery), [
            'status' => 'in_transit',
        ])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.deliveries.status', $delivery), [
                'status' => 'in_transit',
            ])
            ->assertForbidden();

        $this->assertSame('planned', $delivery->fresh()->status);
        $this->assertDatabaseCount('delivery_status_histories', 0);
    }

    public function test_legacy_delivery_details_render_without_synthetic_history(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery(['status' => 'delivered']);

        $this->actingAs($admin)
            ->get(route('admin.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Livraison créée')
            ->assertSee('Les livraisons antérieures au suivi conservent leur état actuel sans historique rétroactif.')
            ->assertSee('Dernière mise à jour du statut');

        $this->assertDatabaseCount('delivery_status_histories', 0);
    }

    public function test_existing_delivery_crud_creates_planned_delivery_and_cannot_bypass_workflow(): void
    {
        $admin = $this->admin();
        $rental = $this->rental();
        $this->actingAs($admin);

        $this->get(route('admin.deliveries.create'))->assertOk()->assertSee('Planifiée');
        $this->post(route('admin.deliveries.store'), [
            'rental_id' => $rental->id,
            'type' => 'home_delivery',
            'scheduled_date' => '2026-11-05',
            'scheduled_time' => '10:30',
            'delivery_fee' => 12,
            'status' => 'returned',
        ])->assertRedirect(route('admin.deliveries.index'));

        $delivery = Delivery::firstOrFail();
        $this->assertSame('planned', $delivery->status);

        $this->put(route('admin.deliveries.update', $delivery), [
            'rental_id' => $rental->id,
            'type' => 'home_delivery',
            'scheduled_date' => '2026-11-06',
            'scheduled_time' => '11:30',
            'delivery_fee' => 14,
            'status' => 'delivered',
        ])->assertRedirect(route('admin.deliveries.index'));

        $this->assertSame('planned', $delivery->fresh()->status);
        $this->assertSame('2026-11-06', $delivery->fresh()->scheduled_date->format('Y-m-d'));
        $this->assertDatabaseCount('delivery_status_histories', 0);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function delivery(array $overrides = []): Delivery
    {
        $rental = $this->rental();

        return Delivery::create(array_merge([
            'type' => 'home_delivery',
            'scheduled_date' => '2026-11-05',
            'scheduled_time' => '10:30',
            'status' => 'planned',
            'delivery_fee' => 0,
            'rental_id' => $rental->id,
            'pickup_point_id' => null,
        ], $overrides));
    }

    private function rental(): Rental
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create([
            'name' => 'Category '.uniqid(),
            'icon' => 'fa-solar-panel',
        ]);
        $equipment = Equipment::create([
            'title' => 'Solar panel',
            'description' => 'Test equipment',
            'condition' => 'Good',
            'price_per_day' => 10,
            'deposit' => 0,
            'category_id' => $category->id,
            'owner_id' => $owner->id,
        ]);

        return Rental::create([
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-07',
            'total_price' => 30,
            'status' => 'accepted',
            'equipment_id' => $equipment->id,
            'renter_id' => $renter->id,
        ]);
    }
}
