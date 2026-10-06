<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function equipment(User $owner, string $title = 'Panneau 100W'): Equipment
    {
        $category = Category::firstOrCreate(['name' => 'Panneaux solaires'], ['icon' => 'fa-solar-panel']);

        return Equipment::create(['title' => $title, 'description' => 'Test', 'condition' => 'Bon état', 'price_per_day' => 10, 'category_id' => $category->id, 'owner_id' => $owner->id]);
    }

    public function test_admin_can_manage_maintenances(): void
    {
        $admin = $this->admin();
        $panel = $this->equipment($admin);
        $battery = $this->equipment($admin, 'Batterie 500Wh');
        $this->actingAs($admin);

        $this->get('/admin/maintenances')->assertOk()->assertSee('Aucune maintenance');
        $this->get('/admin/maintenances/create')->assertOk()->assertSee('Batterie 500Wh');

        $this->post('/admin/maintenances', ['equipment_id' => $panel->id, 'type' => 'nettoyage', 'date' => today()->format('Y-m-d'), 'cost' => 12.5, 'notes' => 'Vitres nettoyées'])
            ->assertRedirect('/admin/maintenances');
        $this->post('/admin/maintenances', ['equipment_id' => $battery->id, 'type' => 'inspection', 'date' => today()->subDay()->format('Y-m-d'), 'cost' => 0]);
        $maintenance = Maintenance::where('equipment_id', $panel->id)->firstOrFail();
        $this->assertSame('12.50', $maintenance->cost);
        $this->assertTrue($panel->maintenances->first()->is($maintenance));

        $this->get('/admin/maintenances')->assertOk()->assertSee('Vitres nettoyées')->assertSee('Inspection');
        $this->get('/admin/maintenances?equipment='.$panel->id)->assertOk()->assertSee('Vitres nettoyées')->assertDontSee('Inspection</span>', false);

        $this->get('/admin/maintenances/'.$maintenance->id.'/edit')->assertOk();
        $this->put('/admin/maintenances/'.$maintenance->id, ['equipment_id' => $panel->id, 'type' => 'reparation', 'date' => today()->format('Y-m-d'), 'cost' => 80])->assertRedirect('/admin/maintenances');
        $this->assertDatabaseHas('maintenances', ['id' => $maintenance->id, 'type' => 'reparation', 'notes' => null]);

        $this->delete('/admin/maintenances/'.$maintenance->id)->assertRedirect();
        $this->assertDatabaseMissing('maintenances', ['id' => $maintenance->id]);

        // Deleting an equipment removes its maintenance history.
        $battery->delete();
        $this->assertDatabaseCount('maintenances', 0);
    }

    public function test_maintenance_validation(): void
    {
        $admin = $this->admin();
        $panel = $this->equipment($admin);

        $this->actingAs($admin)->post('/admin/maintenances', ['equipment_id' => 999, 'type' => 'peinture', 'date' => today()->addDay()->format('Y-m-d'), 'cost' => -5, 'notes' => str_repeat('a', 2001)])
            ->assertSessionHasErrors(['equipment_id', 'type', 'date', 'cost', 'notes']);
        $this->post('/admin/maintenances', ['equipment_id' => $panel->id, 'type' => 'nettoyage', 'date' => today()->addDay()->format('Y-m-d'), 'cost' => 0])
            ->assertSessionHasErrors(['date' => 'La date ne peut pas être dans le futur.']);
        $this->assertDatabaseCount('maintenances', 0);
    }

    public function test_only_admins_can_access_maintenance(): void
    {
        $admin = $this->admin();
        $maintenance = Maintenance::create(['equipment_id' => $this->equipment($admin)->id, 'type' => 'nettoyage', 'date' => today(), 'cost' => 0]);
        $user = User::factory()->create();

        $this->get('/admin/maintenances')->assertRedirect('/login');
        $this->actingAs($user)->get('/admin/maintenances')->assertForbidden();
        $this->get('/admin/maintenances/create')->assertForbidden();
        $this->post('/admin/maintenances', [])->assertForbidden();
        $this->put('/admin/maintenances/'.$maintenance->id, [])->assertForbidden();
        $this->delete('/admin/maintenances/'.$maintenance->id)->assertForbidden();
        $this->assertDatabaseCount('maintenances', 1);
    }
}
