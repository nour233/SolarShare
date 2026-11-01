<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Reclamation;
use App\Models\Rental;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reputation_report_with_service_and_client_analysis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Solar']);

        Review::create([
            'user_id' => $client->id,
            'service' => 'maintenance',
            'rating' => 5,
            'title' => 'Très bon service',
            'comment' => 'Une intervention rapide et efficace.',
        ]);

        $this->actingAs($admin)
            ->get('/admin/reputation')
            ->assertOk()
            ->assertSee('Rapport de réputation')
            ->assertSee('Maintenance')
            ->assertSee('Client Solar')
            ->assertSee('Bonne réputation');
    }

    public function test_regular_users_cannot_view_reputation_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/reputation')
            ->assertForbidden();
    }

    public function test_admin_can_download_reputation_report_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client PDF']);
        Review::create([
            'user_id' => $client->id,
            'service' => 'maintenance',
            'rating' => 5,
            'title' => 'Très bon service',
            'comment' => 'Une intervention efficace.',
        ]);

        $this->actingAs($admin)
            ->get('/admin/reputation/download')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('rapport-reputation-solarshare.pdf');
    }

    public function test_admin_can_ask_the_reputation_assistant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Réclamations']);
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        $equipment = Equipment::create([
            'title' => 'Batterie de test',
            'description' => 'Équipement de test',
            'condition' => 'Bon état',
            'price_per_day' => 20,
            'category_id' => $category->id,
            'owner_id' => $admin->id,
        ]);

        Reclamation::create([
            'user_id' => $client->id,
            'type' => 'location',
            'subject' => 'Retard',
            'description' => 'La livraison est en retard.',
            'reclamation_date' => now()->toDateString(),
        ]);
        Rental::create([
            'renter_id' => $client->id,
            'equipment_id' => $equipment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'total_price' => 100,
            'status' => 'returned',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/reputation/assistant', ['question' => 'Qui a le plus de réclamations ?'])
            ->assertOk()
            ->assertJsonPath('source', 'regles')
            ->assertJsonPath('answer', 'Client Réclamations est le client avec le plus de réclamations (1). Client Réclamations a effectué 1 location(s).');
    }

    public function test_regular_users_cannot_ask_the_reputation_assistant(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/admin/reputation/assistant', ['question' => 'Qui a le plus de réclamations ?'])
            ->assertForbidden();
    }
}
