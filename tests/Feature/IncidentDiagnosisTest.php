<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IncidentDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['name' => 'Admin Solar']);
        $this->admin->forceFill(['role' => 'admin'])->save();
        $this->user = User::factory()->create(['name' => 'Fatma']);
        $category = Category::create(['name' => 'Panneaux', 'icon' => 'fa-solar-panel']);
        $this->equipment = Equipment::create(['title' => 'Panneau 400W', 'description' => 'Test', 'condition' => 'Bon état', 'price_per_day' => 25, 'category_id' => $category->id, 'owner_id' => $this->admin->id]);
    }

    private function fakeAi(array $diagnosis): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode($diagnosis)]]]])]);
    }

    private function aiDiagnosis(array $overrides = []): array
    {
        return $overrides + [
            'severity' => 'grave', 'cause' => 'Microfissure sur une cellule.', 'action' => 'reparation',
            'cost_min' => 150, 'cost_max' => 250, 'block_equipment' => true,
            'client_advice' => 'Débranchez le panneau et ne le nettoyez pas à l’eau.',
            'summary' => 'Panneau endommagé par la tempête.', 'confidence' => 'elevee',
        ];
    }

    public function test_report_is_diagnosed_by_ai_and_client_sees_safety_advice(): void
    {
        $this->fakeAi($this->aiDiagnosis());

        $this->actingAs($this->user)->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'Le panneau produit beaucoup moins depuis la tempête.', 'severity' => 'moyen',
        ])->assertRedirect();

        $incident = Incident::sole();
        $this->assertSame('ia', $incident->ai_diagnosis['source']);
        $this->assertSame('grave', $incident->ai_diagnosis['severity']);
        $this->assertTrue($incident->severityUnderestimated());
        Http::assertSent(fn ($request) => $request['response_format']['json_schema']['strict'] === true
            && str_contains($request['messages'][1]['content'][0]['text'], 'tempête'));

        $this->get('/incidents/'.$incident->id)->assertOk()->assertSee('Conseil de sécurité')->assertSee('ne le nettoyez pas');
    }

    public function test_rules_are_used_when_no_key_or_when_ai_fails(): void
    {
        config(['services.openai.key' => null]);
        $this->actingAs($this->user)->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'De la fumée sort de la batterie quand je la charge.', 'severity' => 'mineur',
        ]);
        $diagnosis = Incident::sole()->ai_diagnosis;
        $this->assertSame('regles', $diagnosis['source']);
        $this->assertSame('grave', $diagnosis['severity']);
        $this->assertTrue($diagnosis['block_equipment']);

        config(['services.openai.key' => 'test-key']);
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'down'], 500)]);
        $this->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'Le panneau est couvert de poussière et de sable.', 'severity' => 'moyen',
        ]);
        $diagnosis = Incident::latest('id')->first()->ai_diagnosis;
        $this->assertSame('regles', $diagnosis['source']);
        $this->assertSame('nettoyage', $diagnosis['action']);
    }

    public function test_invalid_ai_answer_falls_back_to_rules(): void
    {
        $this->fakeAi($this->aiDiagnosis(['severity' => 'catastrophique', 'cost_max' => 10]));
        $this->actingAs($this->user)->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'Le câble est arraché, plus rien ne marche.', 'severity' => 'moyen',
        ]);

        $this->assertSame('regles', Incident::sole()->ai_diagnosis['source']);
    }

    public function test_admin_sees_diagnosis_applies_severity_and_plans_maintenance(): void
    {
        $incident = Incident::create(['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => 'Le panneau est fissuré.', 'severity' => 'moyen',
            'ai_diagnosis' => $this->aiDiagnosis() + ['source' => 'ia', 'generated_at' => now()->toDateTimeString()]]);

        $this->actingAs($this->user)->get('/admin/incidents/'.$incident->id)->assertForbidden();

        $this->actingAs($this->admin)->get('/admin/incidents/'.$incident->id)->assertOk()
            ->assertSee('DIAGNOSTIC IA')->assertSee('sous-estimée')->assertSee('Microfissure sur une cellule.')->assertSee('Créer la maintenance');

        $this->patch('/admin/incidents/'.$incident->id.'/severity')->assertRedirect();
        $this->assertSame('grave', $incident->fresh()->severity);

        $this->get('/admin/maintenances/create?incident='.$incident->id)->assertOk()
            ->assertSee('pré-rempli par le diagnostic IA')->assertSee('value="200.00"', false)->assertSee('Microfissure sur une cellule.');

        $this->post('/admin/maintenances', [
            'equipment_id' => $this->equipment->id, 'type' => 'reparation', 'date' => today()->format('Y-m-d'),
            'cost' => 200, 'notes' => 'Incident #'.$incident->id, 'incident_id' => $incident->id,
        ])->assertRedirect('/admin/incidents/'.$incident->id);

        $this->assertSame('en_cours', $incident->fresh()->status);
        $this->assertSame('reparation', Maintenance::sole()->type);
    }

    public function test_admin_can_run_diagnosis_on_an_old_incident(): void
    {
        $incident = Incident::create(['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => 'Bruit étrange et rendement faible.', 'severity' => 'mineur']);
        $this->fakeAi($this->aiDiagnosis(['severity' => 'moyen', 'action' => 'inspection']));

        $this->actingAs($this->admin)->get('/admin/incidents/'.$incident->id)->assertSee('Analyser avec l’IA');
        $this->post('/admin/incidents/'.$incident->id.'/diagnosis')->assertRedirect();

        $this->assertSame('inspection', $incident->fresh()->ai_diagnosis['action']);
    }
}
