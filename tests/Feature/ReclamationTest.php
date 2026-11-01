<?php

namespace Tests\Feature;

use App\Models\Reclamation;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Equipment;
use App\Models\Rental;
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
        $this->assertDatabaseHas('reclamations', ['user_id' => $user->id, 'sentiment_label' => 'negative']);
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
        $this->actingAs($admin)->patch('/admin/reclamations/'.$reclamation->id.'/status', [
            'status' => 'resolue',
            'admin_response' => 'Votre demande a été traitée par notre équipe.',
        ])->assertRedirect();
        $this->assertDatabaseHas('reclamations', [
            'id' => $reclamation->id,
            'status' => 'resolue',
            'admin_response' => 'Votre demande a été traitée par notre équipe.',
        ]);
        $this->actingAs($user)->get('/reclamations/'.$reclamation->id)
            ->assertOk()
            ->assertSee('Votre demande a été traitée par notre équipe.');
    }

    public function test_review_reclamation_requires_and_stores_review_author_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reclamations', [
            'type' => 'avis',
            'subject' => 'Avis inapproprié',
            'description' => 'Cet avis ne respecte pas les règles de notre communauté.',
            'reclamation_date' => today()->format('Y-m-d'),
        ])->assertSessionHasErrors('review_author_name');

        $this->actingAs($user)->post('/reclamations', [
            'type' => 'avis',
            'review_author_name' => 'Client Solar',
            'subject' => 'Avis inapproprié',
            'description' => 'Cet avis ne respecte pas les règles de notre communauté.',
            'reclamation_date' => today()->format('Y-m-d'),
        ])->assertRedirect('/reclamations');

        $this->assertDatabaseHas('reclamations', [
            'user_id' => $user->id,
            'type' => 'avis',
            'review_author_name' => 'Client Solar',
        ]);
    }

    public function test_client_can_filter_reclamations_without_a_full_page_response(): void
    {
        $user = User::factory()->create();
        Reclamation::create([
            'user_id' => $user->id,
            'type' => 'maintenance',
            'subject' => 'Maintenance urgente',
            'description' => 'Une maintenance urgente est nécessaire.',
            'reclamation_date' => today(),
            'status' => 'en_cours',
        ]);
        Reclamation::create([
            'user_id' => $user->id,
            'type' => 'livraison',
            'subject' => 'Question livraison',
            'description' => 'Je souhaite connaître la date de livraison.',
            'reclamation_date' => today()->subDay(),
        ]);

        $this->actingAs($user)
            ->getJson('/reclamations?search=urgente&status=en_cours&sort=subject')
            ->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Maintenance urgente') && ! str_contains($html, 'Question livraison'));
    }

    public function test_admin_can_search_filter_and_sort_reclamations_without_a_full_page_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Recherche']);
        Reclamation::create([
            'user_id' => $client->id,
            'type' => 'avis',
            'subject' => 'Avis à examiner',
            'description' => 'Cet avis doit être examiné par l’administration.',
            'reclamation_date' => today(),
            'status' => 'en_cours',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/reclamations?search=Recherche&status=en_cours&sort=client')
            ->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Client Recherche') && str_contains($html, 'Avis à examiner'));
    }

    public function test_admin_can_generate_a_reclamation_response_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Réclamation']);
        $reclamation = Reclamation::create([
            'user_id' => $client->id,
            'type' => 'livraison',
            'subject' => 'Livraison en retard',
            'description' => 'La livraison est arrivée en retard.',
            'reclamation_date' => today(),
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/reclamations/'.$reclamation->id.'/response-draft')
            ->assertOk()
            ->assertJsonPath('source', 'regles')
            ->assertJsonPath('draft', fn (string $draft): bool => str_contains($draft, 'Client Réclamation') && str_contains($draft, 'Livraison en retard'));
    }

    public function test_regular_users_cannot_generate_an_admin_response_draft(): void
    {
        $user = User::factory()->create();
        $reclamation = Reclamation::create([
            'user_id' => $user->id,
            'type' => 'location',
            'subject' => 'Ma location',
            'description' => 'J’ai une question.',
            'reclamation_date' => today(),
        ]);

        $this->actingAs($user)
            ->postJson('/admin/reclamations/'.$reclamation->id.'/response-draft')
            ->assertForbidden();
    }

    public function test_delivery_response_draft_uses_the_real_delivery_status_and_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Livraison']);
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        $equipment = Equipment::create([
            'title' => 'Batterie livraison',
            'description' => 'Équipement de test',
            'condition' => 'Bon état',
            'price_per_day' => 20,
            'category_id' => $category->id,
            'owner_id' => $admin->id,
        ]);
        $rental = Rental::create([
            'renter_id' => $client->id,
            'equipment_id' => $equipment->id,
            'start_date' => today(),
            'end_date' => today()->addDays(3),
            'total_price' => 80,
            'status' => 'accepted',
        ]);
        Delivery::create([
            'rental_id' => $rental->id,
            'type' => 'home_delivery',
            'scheduled_date' => today()->addDay(),
            'scheduled_time' => '10:00',
            'status' => 'in_transit',
            'delivery_fee' => 0,
        ]);
        $reclamation = Reclamation::create([
            'user_id' => $client->id,
            'type' => 'livraison',
            'subject' => 'Suivi de livraison',
            'description' => 'Je voudrais connaître l’avancement de ma livraison.',
            'reclamation_date' => today(),
            'status' => 'en_cours',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/reclamations/'.$reclamation->id.'/response-draft')
            ->assertOk()
            ->assertJsonPath('source', 'regles')
            ->assertJsonPath('draft', fn (string $draft): bool => str_contains($draft, 'en cours d’acheminement')
                && str_contains($draft, today()->addDay()->format('d/m/Y')));
    }
}
