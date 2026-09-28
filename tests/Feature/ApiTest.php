<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_that_works(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['token'])->json('token');

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_notes_require_a_token(): void
    {
        $this->getJson('/api/notes')->assertUnauthorized();
    }

    public function test_full_crud_over_the_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/notes', ['title' => 'From the API', 'body' => 'Hello'])
            ->assertCreated()
            ->assertJsonPath('title', 'From the API')
            ->json('id');

        $this->getJson('/api/notes')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson("/api/notes/{$id}")->assertOk()->assertJsonPath('body', 'Hello');

        $this->putJson("/api/notes/{$id}", ['title' => 'Changed'])
            ->assertOk()
            ->assertJsonPath('title', 'Changed');

        $this->deleteJson("/api/notes/{$id}")->assertNoContent();
        $this->getJson("/api/notes/{$id}")->assertNotFound();
    }

    public function test_validation_errors_come_back_as_json(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/notes', ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_api_cannot_read_someone_elses_note(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $theirs = Note::factory()->create();

        $this->getJson("/api/notes/{$theirs->id}")->assertNotFound();
        $this->deleteJson("/api/notes/{$theirs->id}")->assertNotFound();
        $this->assertModelExists($theirs);
    }
}
