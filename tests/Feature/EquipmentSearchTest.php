<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentSearchTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Equipment
    {
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        return Equipment::create(['title' => 'Batterie camping', 'description' => 'Capacité 500 Wh',
            'category_id' => $category->id, 'owner_id' => User::factory()->create()->id,
            'power_capacity' => 500, 'power_unit' => 'Wh', 'condition' => 'Bon état', 'price_per_day' => 20, 'deposit' => 50]);
    }

    public function test_public_search_uses_real_catalogue_and_rejects_invented_ids(): void
    {
        Http::preventStrayRequests();
        config(['services.anthropic.key' => 'test-only']);
        $product = $this->product();
        $reply = fn ($id) => ['content' => [['type' => 'tool_use', 'name' => 'recommend', 'input' => ['items' => [['id' => $id, 'reason' => 'Une piste pour le camping ; compatibilité à vérifier.']]]]]];
        Http::fake(['api.anthropic.com/*' => Http::sequence()->push($reply($product->id))->push($reply(999999))]);
        $this->postJson(route('equipments.ai-search'), ['need' => 'Charger un téléphone en camping'])
            ->assertOk()->assertJsonPath('items.0.title', $product->title)->assertJsonPath('items.0.price', '20,00')
            ->assertJsonPath('items.0.url', route('equipments.show', $product));
        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'Batterie camping'));
        $this->postJson(route('equipments.ai-search'), ['need' => 'Charger un téléphone en camping'])->assertStatus(502);
        $this->assertDatabaseCount('equipment', 1);
    }

    public function test_validation_empty_matches_and_provider_failure(): void
    {
        Http::preventStrayRequests();
        $url = route('equipments.ai-search');
        $this->postJson($url, ['need' => 'abc'])->assertUnprocessable();
        config(['services.anthropic.key' => null]);
        $this->postJson($url, ['need' => 'Énergie pour camping'])->assertStatus(503);
        Http::assertNothingSent();
        config(['services.anthropic.key' => 'test-only']);
        $this->product();
        Http::fake(['api.anthropic.com/*' => Http::sequence()->push(['content' => [['type' => 'tool_use', 'name' => 'recommend', 'input' => ['items' => []]]]])->push(['error' => 'private-details'], 401)]);
        $this->postJson($url, ['need' => 'Énergie pour camping'])->assertOk()->assertJsonPath('items', []);
        $this->postJson($url, ['need' => 'Énergie pour camping'])->assertStatus(502)->assertDontSee('private-details');
    }
}
