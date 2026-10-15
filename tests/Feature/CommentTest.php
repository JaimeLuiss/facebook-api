<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_a_comment(): void
    {
        $user = User::factory()->create([
            'name' => 'Carlos Gomez',
        ]);

        $post = Post::create([
            'title' => 'Test post',
            'content' => 'Post content',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => '¡Qué buena foto!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('author', 'Carlos Gomez')
            ->assertJsonPath('content', '¡Qué buena foto!')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'author' => 'Carlos Gomez',
            'content' => '¡Qué buena foto!',
        ]);
    }
}
