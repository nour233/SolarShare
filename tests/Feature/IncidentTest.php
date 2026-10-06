<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentTest extends TestCase
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
        $this->user = User::factory()->create(['name' => 'Nour']);
        $category = Category::create(['name' => 'Batteries', 'icon' => 'fa-battery-full']);
        $this->equipment = Equipment::create(['title' => 'Batterie 500Wh', 'description' => 'Test', 'condition' => 'Bon état', 'price_per_day' => 20, 'category_id' => $category->id, 'owner_id' => $this->admin->id]);
    }

    /** A fake image that does not need the GD extension. */
    private function photo(string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 200, 'image/jpeg');
    }

    private function incidentFor(User $reporter, string $status = 'ouvert'): Incident
    {
        return Incident::create(['equipment_id' => $this->equipment->id, 'user_id' => $reporter->id, 'description' => 'La batterie ne charge plus.', 'severity' => 'moyen', 'status' => $status]);
    }

    private function unreadCount(User $user): int
    {
        return $this->actingAs($user)->getJson('/incidents/unread-count')->assertOk()->json('count');
    }

    public function test_user_reports_an_incident_with_photos(): void
    {
        Storage::fake('public');

        $this->get('/equipments/'.$this->equipment->id)->assertSee('Connectez-vous')->assertDontSee('Signaler un problème');
        $this->get('/equipments/'.$this->equipment->id.'/incidents/create')->assertRedirect('/login');

        $this->actingAs($this->user)->get('/equipments/'.$this->equipment->id)->assertSee('Signaler un problème');
        $this->get('/equipments/'.$this->equipment->id.'/incidents/create')->assertOk()->assertSee('Batterie 500Wh');
        $this->get('/incidents')->assertOk()->assertSee('Aucun signalement pour le moment');

        $response = $this->post('/equipments/'.$this->equipment->id.'/incidents', [
            'description' => 'La batterie se vide en une heure.',
            'severity' => 'grave',
            'photos' => [$this->photo('a.jpg'), $this->photo('b.png')],
        ]);

        $incident = Incident::firstOrFail();
        $response->assertRedirect('/incidents/'.$incident->id);
        $this->assertSame('ouvert', $incident->status);
        $this->assertTrue($incident->user->is($this->user));
        $this->assertNull($incident->rental_id);
        $this->assertCount(2, $incident->photos);
        Storage::disk('public')->assertExists(substr($incident->photos[0], 8));

        $this->get('/incidents')->assertOk()->assertSee('Batterie 500Wh')->assertSee('Ouvert');
        $this->get('/incidents/'.$incident->id)->assertOk()->assertSee('La batterie se vide en une heure.')->assertSee('Grave');
    }

    public function test_report_validation(): void
    {
        $photos = array_map(fn ($i) => $this->photo("p$i.jpg"), range(1, 5));

        $this->actingAs($this->user)->post('/equipments/'.$this->equipment->id.'/incidents', ['description' => 'Court', 'severity' => 'enorme', 'photos' => $photos])
            ->assertSessionHasErrors(['description', 'severity', 'photos']);
        $this->post('/equipments/'.$this->equipment->id.'/incidents', ['description' => 'Une description valide.', 'severity' => 'mineur', 'photos' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_user_cannot_see_or_write_in_someone_elses_incident(): void
    {
        $incident = $this->incidentFor($this->user);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get('/incidents/'.$incident->id)->assertForbidden();
        $this->getJson('/incidents/'.$incident->id.'/messages')->assertForbidden();
        $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Coucou'])->assertForbidden();
        $this->get('/incidents')->assertOk()->assertDontSee('Batterie 500Wh');
        $this->get('/admin/incidents')->assertForbidden();
        $this->get('/admin/incidents/'.$incident->id)->assertForbidden();
        $this->assertDatabaseCount('incident_messages', 0);

        // The reporter and the admin can open it.
        $this->actingAs($this->user)->get('/incidents/'.$incident->id)->assertOk();
        $this->actingAs($this->admin)->get('/incidents/'.$incident->id)->assertOk();
        $this->get('/admin/incidents/'.$incident->id)->assertOk();
    }

    public function test_only_admin_changes_status_edits_or_deletes(): void
    {
        $incident = $this->incidentFor($this->user);

        $this->actingAs($this->user)->patch('/admin/incidents/'.$incident->id.'/status', ['status' => 'resolu'])->assertForbidden();
        $this->put('/admin/incidents/'.$incident->id, [])->assertForbidden();
        $this->delete('/admin/incidents/'.$incident->id)->assertForbidden();
        $this->assertSame('ouvert', $incident->fresh()->status);

        $this->actingAs($this->admin)->get('/admin/incidents')->assertOk()->assertSee('Batterie 500Wh')->assertSee('Nour');
        $this->patch('/admin/incidents/'.$incident->id.'/status', ['status' => 'en_cours'])->assertRedirect();
        $this->assertSame('en_cours', $incident->fresh()->status);
        $this->patch('/admin/incidents/'.$incident->id.'/status', ['status' => 'perdu'])->assertSessionHasErrors('status');
        $this->get('/admin/incidents?status=en_cours')->assertOk()->assertSee('Batterie 500Wh');
        $this->get('/admin/incidents?status=resolu')->assertOk()->assertSee('Aucun incident avec ce statut.');
    }

    public function test_admin_incident_crud(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $this->get('/admin/incidents/create')->assertOk()->assertSee('Nour');
        $this->post('/admin/incidents', ['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => 'Signalé par téléphone.', 'severity' => 'mineur', 'status' => 'ouvert', 'photos' => [$this->photo()]])
            ->assertRedirect('/admin/incidents');
        $incident = Incident::firstOrFail();
        $photo = $incident->photos[0];
        $this->assertTrue($incident->isReporter($this->user));

        $this->get('/admin/incidents/'.$incident->id.'/edit')->assertOk();
        $this->put('/admin/incidents/'.$incident->id, ['equipment_id' => $this->equipment->id, 'user_id' => $this->user->id, 'description' => 'Description corrigée.', 'severity' => 'grave', 'status' => 'en_cours', 'remove_photos' => [$photo]])
            ->assertRedirect('/admin/incidents/'.$incident->id);
        $this->assertSame([], $incident->fresh()->photos);
        $this->assertSame('grave', $incident->fresh()->severity);
        Storage::disk('public')->assertMissing(substr($photo, 8));

        $incident->messages()->create(['user_id' => $this->user->id, 'body' => 'Bonjour']);
        $this->delete('/admin/incidents/'.$incident->id)->assertRedirect('/admin/incidents');
        $this->assertDatabaseCount('incidents', 0);
        $this->assertDatabaseCount('incident_messages', 0);
    }

    public function test_messages_both_ways_and_unread_counts(): void
    {
        $incident = $this->incidentFor($this->user);

        // The reporter writes (with fetch, then without JavaScript).
        $this->actingAs($this->user)->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Bonjour, la batterie chauffe.'])
            ->assertCreated()->assertJsonPath('message.mine', true)->assertJsonPath('message.author', 'Nour');
        $this->from('/incidents/'.$incident->id)->post('/incidents/'.$incident->id.'/messages', ['body' => 'Photo à venir.'])
            ->assertRedirect('/incidents/'.$incident->id.'#conversation');
        $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => ''])->assertUnprocessable()->assertJsonValidationErrors('body');

        $this->assertSame(0, $this->unreadCount($this->user));
        $this->assertSame(2, $this->unreadCount($this->admin));
        $this->get('/admin/incidents')->assertSee('<span class="unread-badge ms-0" title="Messages non lus">2</span>', false);

        // Opening the incident marks the reporter's messages as read for the admin.
        $this->get('/admin/incidents/'.$incident->id)->assertOk()->assertSee('Bonjour, la batterie chauffe.');
        $this->assertSame(0, $this->unreadCount($this->admin));

        // The admin answers: the reporter gets a badge.
        $reply = $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Merci, nous regardons.'])->assertCreated()->json('message');
        $this->assertSame(1, $this->unreadCount($this->user));
        $this->assertSame(0, $this->unreadCount($this->admin));
        $this->actingAs($this->user)->get('/incidents')->assertSee('non lu');

        // Polling returns only newer messages, from the reporter's point of view, and marks them read.
        $this->getJson('/incidents/'.$incident->id.'/messages?after='.($reply['id'] - 1))
            ->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Merci, nous regardons.')
            ->assertJsonPath('messages.0.mine', false)->assertJsonPath('closed', false);
        $this->assertSame(0, $this->unreadCount($this->user));
        $this->getJson('/incidents/'.$incident->id.'/messages?after='.$reply['id'])->assertJsonCount(0, 'messages');
    }

    public function test_opening_the_page_resets_the_reporter_badge(): void
    {
        $incident = $this->incidentFor($this->user);
        $incident->messages()->create(['user_id' => $this->admin->id, 'body' => 'Pouvez-vous envoyer une photo ?']);
        $this->assertSame(1, $this->unreadCount($this->user));

        $this->get('/incidents/'.$incident->id)->assertOk()->assertSee('Pouvez-vous envoyer une photo ?');
        $this->assertSame(0, $this->unreadCount($this->user));
        $this->assertNotNull($incident->messages()->first()->read_at);
    }

    public function test_closed_incident_is_read_only(): void
    {
        $incident = $this->incidentFor($this->user, 'resolu');

        $this->actingAs($this->user)->get('/incidents/'.$incident->id)->assertOk()->assertSee('lecture seule')->assertDontSee('id="chat-form"', false);
        $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Encore un souci'])->assertStatus(422)->assertJsonPath('closed', true);
        $this->from('/incidents/'.$incident->id)->post('/incidents/'.$incident->id.'/messages', ['body' => 'Encore un souci'])->assertSessionHasErrors('body');
        $this->actingAs($this->admin)->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Réponse'])->assertStatus(422);
        $this->getJson('/incidents/'.$incident->id.'/messages')->assertJsonPath('closed', true);
        $this->assertDatabaseCount('incident_messages', 0);
    }

    public function test_message_bodies_are_escaped_and_sending_is_throttled(): void
    {
        $incident = $this->incidentFor($this->user);
        $this->actingAs($this->user)->postJson('/incidents/'.$incident->id.'/messages', ['body' => '<script>alert(1)</script>'])->assertCreated();

        $this->get('/incidents/'.$incident->id)->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        for ($i = 2; $i <= 20; $i++) {
            $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Message '.$i])->assertCreated();
        }
        $this->postJson('/incidents/'.$incident->id.'/messages', ['body' => 'Un de trop'])->assertStatus(429);
    }
}
