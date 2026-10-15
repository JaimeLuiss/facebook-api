<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_comment_can_be_created_without_a_user_id(): void
    {
        $post = Post::create([
            'title' => 'Test post',
            'content' => 'Post content',
        ]);

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'author' => 'Carlos Gomez',
            'content' => '¡Qué buena foto!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('author', 'Carlos Gomez')
            ->assertJsonPath('content', '¡Qué buena foto!');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => null,
            'author' => 'Carlos Gomez',
            'content' => '¡Qué buena foto!',
        ]);
    }
}
