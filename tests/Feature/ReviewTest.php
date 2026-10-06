<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_are_public_but_only_authenticated_users_can_publish(): void
    {
        $user = User::factory()->create();

        $this->get('/reviews')->assertOk()->assertSee('Les avis de nos clients');
        $this->get('/reviews/create')->assertRedirect('/login');
        $this->actingAs($user)->post('/reviews', [
            'rating' => 5,
            'title' => 'Excellent service',
            'comment' => 'Une très bonne expérience avec SolarShare.',
        ])->assertRedirect('/reviews');

        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'rating' => 5]);
        $this->get('/reviews')->assertSee('Excellent service');
    }

    public function test_latest_reviews_are_displayed_on_the_homepage(): void
    {
        $user = User::factory()->create(['name' => 'Client Solar']);
        Review::create([
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Accueil parfait',
            'comment' => 'Une expérience excellente avec SolarShare.',
        ]);

        $this->get('/')->assertOk()->assertSee('Accueil parfait')->assertSee('Une expérience excellente avec SolarShare.');
    }

    public function test_owner_can_edit_or_delete_only_within_five_minutes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::create(['user_id' => $user->id, 'rating' => 4, 'title' => 'Bon service', 'comment' => 'Le service était très satisfaisant.']);

        $this->actingAs($other)->put('/reviews/'.$review->id, [
            'rating' => 1, 'title' => 'Intrusion', 'comment' => 'Tentative non autorisée.',
        ])->assertForbidden();

        $this->actingAs($user)->put('/reviews/'.$review->id, [
            'rating' => 5, 'title' => 'Très bon service', 'comment' => 'Le service était vraiment excellent.',
        ])->assertRedirect('/reviews');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'title' => 'Très bon service']);

        $this->travel(6)->minutes();
        $this->actingAs($user)->get('/reviews/'.$review->id.'/edit')->assertForbidden();
        $this->actingAs($user)->delete('/reviews/'.$review->id)->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }
}
