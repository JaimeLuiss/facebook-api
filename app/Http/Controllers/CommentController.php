<?php

namespace App\Http\Controllers;

use App\Mail\NewCommentMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CommentController extends Controller
{
    /**
     * Listar los comentarios de una publicación.
     *
     * @group Comentarios
     *
     * @urlParam postId integer required ID de la publicación. Example: 1
     *
     * @response 200 [{"id":1,"post_id":1,"user_id":2,"author":"Carlos Gomez","content":"Buen post"}]
     */
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        $comments = $post->comments()
            ->latest()
            ->get();

        return response()->json($comments, 200);
    }

    /**
     * Agregar un comentario y notificar al propietario de la publicación.
     *
     * @group Comentarios
     *
     * @authenticated
     *
     * @urlParam postId integer required ID de la publicación. Example: 1
     *
     * @bodyParam content string required Texto del comentario. Example: Buen post
     *
     * @response 201 {"id":1,"post_id":1,"user_id":2,"author":"Carlos Gomez","content":"Buen post"}
     */
    public function store(Request $request, $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::with('user')->findOrFail($postId);
        $user = $request->user();

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'author' => $user->name,
            'content' => $validated['content'],
        ]);

        if ($post->user !== null) {
            Mail::to($post->user->email)->send(new NewCommentMail($comment));
        }

        return response()->json($comment, 201);
    }
}
