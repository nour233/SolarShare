<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_validated_suggestions_without_creating_equipment(): void
    {
        Http::preventStrayRequests();
        config(['services.anthropic.key' => 'test-only']);
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $category = Category::create(['name' => 'Solaire', 'icon' => 'fa-solar-panel']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [[
            'type' => 'tool_use', 'name' => 'suggest_equipment', 'input' => [
                'recognized' => true, 'title' => 'Panneau solaire', 'description' => 'Panneau visible sur la photo.',
                'category_id' => $category->id, 'power_capacity' => null, 'power_unit' => null,
            ],
        ]]])]);
        $this->actingAs($user)->postJson(route('admin.equipments.analyze-photo'), ['photo' => UploadedFile::fake()->image('panel.jpg')])
            ->assertOk()->assertJsonPath('suggestion.category_id', $category->id)->assertJsonPath('suggestion.power_capacity', null);
        Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'test-only') && $request['messages'][0]['content'][0]['source']['type'] === 'base64');
        $this->assertDatabaseCount('equipment', 0);
    }

    public function test_access_validation_missing_key_and_provider_failure(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $url = route('admin.equipments.analyze-photo');
        $this->actingAs($user)->postJson($url)->assertForbidden();
        $user->forceFill(['role' => 'admin'])->save();
        $this->actingAs($user)->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('photo');
        config(['services.anthropic.key' => null]);
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('panel.jpg')])->assertStatus(503);
        Http::assertNothingSent();
        config(['services.anthropic.key' => 'test-only']);
        Category::create(['name' => 'Solaire', 'icon' => 'fa-solar-panel']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'secret must not leak'], 401)]);
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('panel.jpg')])->assertStatus(502)->assertDontSee('secret must not leak');
    }
}
