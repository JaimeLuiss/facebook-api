<?php

namespace Tests\Feature;

use App\Mail\NewCommentMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_list_is_paginated_and_searches_title_or_content(): void
    {
        foreach (range(1, 11) as $number) {
            Post::create([
                'title' => "Publicación {$number}",
                'content' => 'Contenido de prueba',
            ]);
        }
        Post::create([
            'title' => 'Viaje a Cali',
            'content' => 'Un día soleado',
        ]);
        Post::create([
            'title' => 'Otra publicación',
            'content' => 'Recuerdos del viaje',
        ]);

        $page = $this->getJson('/api/v1/posts?page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('data.0.likes_count', 0)
            ->assertJsonCount(3, 'data');

        $this->assertEquals(13, $page->json('total'));

        $this->getJson('/api/v1/posts?search=viaje')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_legacy_post_list_remains_available(): void
    {
        $this->getJson('/api/posts')->assertOk();
    }

    public function test_protected_v1_endpoints_reject_anonymous_requests(): void
    {
        $post = Post::create([
            'title' => 'Publicación',
            'content' => 'Contenido',
        ]);

        $this->postJson('/api/v1/posts')->assertUnauthorized();
        $this->postJson("/api/v1/posts/{$post->id}/comments")->assertUnauthorized();
        $this->deleteJson("/api/v1/posts/{$post->id}")->assertUnauthorized();
    }

    public function test_post_images_use_the_configured_disk_and_return_its_url(): void
    {
        config(['filesystems.image_disk' => 's3']);
        Storage::fake('s3');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/v1/posts', [
            'title' => 'Publicación con imagen',
            'content' => 'Contenido de prueba',
            'images' => [UploadedFile::fake()->createWithContent(
                'photo.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j3ioAAAAASUVORK5CYII=')
            )],
        ]);

        $response->assertCreated();
        $path = $response->json('images.0.image_path');
        Storage::disk('s3')->assertExists($path);
        $this->assertSame(
            Storage::disk('s3')->url($path),
            $response->json('images.0.image_url')
        );

        $this->deleteJson('/api/v1/posts/'.$response->json('id'))->assertNoContent();
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_generated_api_documentation_is_available(): void
    {
        $this->get('/docs')->assertOk()->assertSee('/api/v1/posts');
    }

    public function test_only_an_authenticated_user_can_like_and_each_user_counts_once(): void
    {
        $post = Post::create([
            'title' => 'Publicación',
            'content' => 'Contenido',
        ]);
        $user = User::factory()->create();

        $this->postJson("/api/v1/posts/{$post->id}/like")->assertUnauthorized();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 1);

        $this->postJson("/api/v1/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 1);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/posts/{$post->id}/like")
            ->assertOk()
            ->assertJsonPath('likes_count', 2);

        $this->assertDatabaseCount('post_user', 2);
        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('likes_count', 2);
    }

    public function test_comment_notifies_the_post_owner_by_email(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $post = $owner->posts()->create([
            'title' => 'Publicación',
            'content' => 'Contenido',
        ]);

        Sanctum::actingAs($commenter);

        $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'content' => 'Buen post',
        ])->assertCreated();

        Mail::assertSent(NewCommentMail::class, function (NewCommentMail $mail) use ($owner): bool {
            return $mail->hasTo($owner->email)
                && $mail->comment->content === 'Buen post';
        });
    }
}
