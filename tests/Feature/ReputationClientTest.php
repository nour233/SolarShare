<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReputationClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_review_clients_and_their_reviews(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['name' => 'Client Avis']);
        Review::create([
            'user_id' => $client->id,
            'service' => 'general',
            'rating' => 2,
            'title' => 'Expérience moyenne',
            'comment' => 'Le service peut encore être amélioré.',
        ]);

        $this->actingAs($admin)->get('/admin/reputation/clients')
            ->assertOk()->assertSee('Client Avis')->assertSee('Voir les avis');
        $this->actingAs($admin)->get('/admin/reputation/clients/'.$client->id)
            ->assertOk()->assertSee('Expérience moyenne')->assertSee('Client Avis');
    }

    public function test_admin_can_delete_a_review_from_client_reputation_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create();
        $review = Review::create([
            'user_id' => $client->id,
            'service' => 'general',
            'rating' => 1,
            'title' => 'À signaler',
            'comment' => 'Avis à retirer du rapport de réputation.',
        ]);

        $this->actingAs($admin)->delete('/admin/reputation/reviews/'.$review->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
