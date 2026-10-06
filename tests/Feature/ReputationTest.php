<?php

namespace Tests\Feature;

use App\Models\Review;
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
}
