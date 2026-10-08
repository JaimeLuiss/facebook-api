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
        $request->validate([
            'author' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);

        $comment = $post->comments()->create([
            'author' => $request->author,
            'content' => $request->content,
        ]);

        return response()->json($comment, 201);
    }
}