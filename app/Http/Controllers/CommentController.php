<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        $comments = $post->comments()
            ->latest()
            ->get();

        return response()->json($comments, 200);
    }

    public function store(Request $request, $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);
        $user = $request->user();

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'author' => $user->name,
            'content' => $validated['content'],
        ]);

        return response()->json($comment, 201);
    }
}