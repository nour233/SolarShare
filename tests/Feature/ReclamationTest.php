<?php

namespace Tests\Feature;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReclamationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_only_see_their_reclamations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherReclamation = Reclamation::create([
            'user_id' => $other->id,
            'type' => 'location',
            'subject' => 'Autre demande',
            'description' => 'Une autre réclamation suffisamment détaillée.',
            'reclamation_date' => today(),
        ]);

        $this->get('/reclamations')->assertRedirect('/login');
        $this->actingAs($user)->get('/reclamations/create')->assertOk()->assertSee('Déposer une réclamation');
        $this->actingAs($user)->post('/reclamations', [
            'type' => 'equipement',
            'subject' => 'Panne du panneau',
            'description' => 'Le panneau ne fonctionne plus depuis hier.',
            'reclamation_date' => today()->format('Y-m-d'),
        ])->assertRedirect('/reclamations');

        $this->assertDatabaseHas('reclamations', ['user_id' => $user->id, 'type' => 'equipement']);
        $this->actingAs($user)->get('/reclamations')->assertSee('Panne du panneau')->assertDontSee('Autre demande');
        $this->actingAs($user)->get('/reclamations/'.$otherReclamation->id)->assertForbidden();
    }

    public function test_admin_can_manage_reclamation_status_and_access_is_protected(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $reclamation = Reclamation::create([
            'user_id' => $user->id,
            'type' => 'maintenance',
            'subject' => 'Maintenance non effectuée',
            'description' => 'La maintenance prévue n’a pas été effectuée.',
            'reclamation_date' => today(),
        ]);

        $this->actingAs($user)->get('/admin/reclamations')->assertForbidden();
        $this->actingAs($admin)->get('/admin/reclamations')->assertOk()->assertSee('Maintenance non effectuée');
        $this->actingAs($admin)->patch('/admin/reclamations/'.$reclamation->id.'/status', ['status' => 'en_cours'])
            ->assertRedirect();
        $this->assertDatabaseHas('reclamations', ['id' => $reclamation->id, 'status' => 'en_cours']);
    }
}
