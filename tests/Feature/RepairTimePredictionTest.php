<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\RepairTimePredictor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RepairTimePredictionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => 'admin'])->save();
        $this->user = User::factory()->create();
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        $this->equipment = Equipment::create(['title' => 'Batterie 500Wh', 'description' => 'Test', 'condition' => 'Bon état', 'price_per_day' => 20, 'category_id' => $category->id, 'owner_id' => $this->admin->id]);
    }

    public function test_client_sees_the_estimate_when_reporting(): void
    {
        // phpunit.xml disables Python: the average per intervention type is used.
        $this->actingAs($this->user)->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'La batterie est gonflée et chauffe beaucoup.', 'severity' => 'moyen',
        ]);

        $incident = Incident::sole();
        $this->assertSame('moyenne', $incident->repair_estimate['source']);
        $this->assertSame('reparation', $incident->ai_diagnosis['action']);
        $expected = (int) round(RepairTimePredictor::metrics()['action_average_days']['reparation'] ?? 6);
        $this->assertSame($expected, $incident->repair_estimate['days']);

        $this->get('/incidents/'.$incident->id)->assertOk()->assertSee('Réparation estimée')
            ->assertSee($incident->created_at->copy()->addDays($expected)->format('d/m/Y'));
        $this->actingAs($this->admin)->get('/admin/incidents/'.$incident->id)->assertOk()->assertSee('DÉLAI ESTIMÉ (ML)');
    }

    public function test_maintenance_keeps_the_real_duration_for_retraining(): void
    {
        $incident = Incident::create(['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => 'La batterie ne charge plus.', 'severity' => 'moyen']);

        $this->actingAs($this->admin)->post('/admin/maintenances', [
            'equipment_id' => $this->equipment->id, 'type' => 'reparation', 'date' => today()->format('Y-m-d'),
            'cost' => 80, 'duration_days' => 4, 'incident_id' => $incident->id,
        ])->assertRedirect('/admin/incidents/'.$incident->id);

        $maintenance = Maintenance::sole();
        $this->assertSame(4, $maintenance->duration_days);
        $this->assertSame($incident->id, $maintenance->incident_id);

        $this->put('/admin/maintenances/'.$maintenance->id, [
            'equipment_id' => $this->equipment->id, 'type' => 'reparation', 'date' => today()->format('Y-m-d'), 'cost' => 80, 'duration_days' => 0,
        ])->assertSessionHasErrors('duration_days');

        $this->get('/admin/maintenances')->assertSee('4 j');
    }

    public function test_only_admin_sees_the_model_metrics(): void
    {
        $this->actingAs($this->user)->get('/admin/maintenances/model')->assertForbidden();

        $response = $this->actingAs($this->admin)->get('/admin/maintenances/model')->assertOk();
        RepairTimePredictor::metrics()
            ? $response->assertSee('MAE')->assertSee('Prédiction vs réalité')->assertSee('Mots de la description')
            : $response->assertSee('pas encore entraîné');
    }

    /** Runs the real Python model when it is available on this machine. */
    public function test_real_model_predicts_longer_repairs_for_serious_problems(): void
    {
        $python = (new Process(['python', '--version']));
        $python->run();
        if (! $python->isSuccessful() || ! is_file(base_path(RepairTimePredictor::MODEL_PATH))) {
            $this->markTestSkipped('Python or the trained model is not available.');
        }
        config(['services.ml.python' => 'python']);

        $estimate = fn (string $description, string $severity, string $action) => app(RepairTimePredictor::class)->predict(
            Incident::create(['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => $description, 'severity' => $severity,
                'ai_diagnosis' => ['severity' => $severity, 'action' => $action]]));

        $dirty = $estimate('Poussière dans les aérations, l’appareil est sale.', 'mineur', 'nettoyage');
        $swollen = $estimate('La batterie est gonflée et le boîtier est déformé.', 'grave', 'reparation');

        $this->assertSame('ml', $dirty['source']);
        $this->assertLessThanOrEqual($dirty['days'], $dirty['min']);
        $this->assertGreaterThanOrEqual($dirty['days'], $dirty['max']);
        $this->assertGreaterThan($dirty['days'] + 2, $swollen['days']);
    }
}
