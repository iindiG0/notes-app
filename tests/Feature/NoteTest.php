<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_own_notes(): void
    {
        $me = User::factory()->create();
        Note::factory()->for($me)->create(['title' => 'My shopping list']);
        Note::factory()->create(['title' => 'Someone elses secret']);

        $this->actingAs($me)->get('/notes')
            ->assertOk()
            ->assertSee('My shopping list')
            ->assertDontSee('Someone elses secret');
    }

    public function test_user_can_create_a_note(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->post('/notes', ['title' => 'Learn CI/CD', 'body' => 'GitHub Actions + Argo CD'])
            ->assertRedirect('/notes');

        $this->assertDatabaseHas('notes', ['user_id' => $me->id, 'title' => 'Learn CI/CD']);
    }

    public function test_title_is_required(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->post('/notes', ['title' => ''])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_user_can_update_their_note(): void
    {
        $me = User::factory()->create();
        $note = Note::factory()->for($me)->create();

        $this->actingAs($me)
            ->put("/notes/{$note->id}", ['title' => 'Updated title', 'body' => 'New body'])
            ->assertRedirect('/notes');

        $this->assertSame('Updated title', $note->fresh()->title);
    }

    public function test_user_can_delete_their_note(): void
    {
        $me = User::factory()->create();
        $note = Note::factory()->for($me)->create();

        $this->actingAs($me)->delete("/notes/{$note->id}")->assertRedirect('/notes');

        $this->assertModelMissing($note);
    }

    public function test_user_cannot_touch_someone_elses_note(): void
    {
        $me = User::factory()->create();
        $theirs = Note::factory()->create();

        $this->actingAs($me)->get("/notes/{$theirs->id}/edit")->assertNotFound();
        $this->actingAs($me)->put("/notes/{$theirs->id}", ['title' => 'Hacked'])->assertNotFound();
        $this->actingAs($me)->delete("/notes/{$theirs->id}")->assertNotFound();

        $this->assertModelExists($theirs);
        $this->assertNotSame('Hacked', $theirs->fresh()->title);
    }
}
