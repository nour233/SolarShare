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
            'service' => 'maintenance',
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
            'service' => 'equipment',
            'rating' => 5,
            'title' => 'Accueil parfait',
            'comment' => 'Une expérience excellente avec SolarShare.',
        ]);

        $this->get('/')->assertOk()->assertSee('Accueil parfait')->assertSee('Une expérience excellente avec SolarShare.');
    }

    public function test_reviews_can_be_filtered_by_rating(): void
    {
        $user = User::factory()->create();
        Review::create(['user_id' => $user->id, 'service' => 'general', 'rating' => 5, 'title' => 'Excellent', 'comment' => 'Une expérience excellente avec SolarShare.']);
        Review::create(['user_id' => $user->id, 'service' => 'general', 'rating' => 2, 'title' => 'Décevant', 'comment' => 'Une expérience qui doit être améliorée.']);

        $this->get('/reviews?rating_filter=5')
            ->assertOk()
            ->assertSee('Excellent')
            ->assertDontSee('Décevant')
            ->assertSee('Réinitialiser');
    }

    public function test_reviews_can_be_searched_by_user_name(): void
    {
        $matchingUser = User::factory()->create(['name' => 'Nadia Solar']);
        $otherUser = User::factory()->create(['name' => 'Karim Energy']);
        Review::create(['user_id' => $matchingUser->id, 'service' => 'general', 'rating' => 5, 'title' => 'Avis de Nadia', 'comment' => 'Une expérience excellente avec SolarShare.']);
        Review::create(['user_id' => $otherUser->id, 'service' => 'general', 'rating' => 5, 'title' => 'Avis de Karim', 'comment' => 'Une expérience excellente avec SolarShare.']);

        $this->get('/reviews?user_search=Nadia')
            ->assertOk()
            ->assertSee('Avis de Nadia')
            ->assertDontSee('Avis de Karim');
    }

    public function test_owner_can_edit_within_five_minutes_but_delete_at_any_time(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $review =         Review::create(['user_id' => $user->id, 'service' => 'rental', 'rating' => 4, 'title' => 'Bon service', 'comment' => 'Le service était très satisfaisant.']);

        $this->actingAs($other)->put('/reviews/'.$review->id, [
            'service' => 'general', 'rating' => 1, 'title' => 'Intrusion', 'comment' => 'Tentative non autorisée.',
        ])->assertForbidden();

        $this->actingAs($user)->put('/reviews/'.$review->id, [
            'service' => 'rental', 'rating' => 5, 'title' => 'Très bon service', 'comment' => 'Le service était vraiment excellent.',
        ])->assertRedirect('/reviews');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'title' => 'Très bon service']);

        $this->travel(6)->minutes();
        $this->actingAs($user)->get('/reviews/'.$review->id.'/edit')->assertForbidden();
        $this->actingAs($user)->delete('/reviews/'.$review->id)->assertRedirect('/reviews');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
