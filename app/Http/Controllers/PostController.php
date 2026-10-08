<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['images', 'comments'])
            ->latest()
            ->get()
            ->map(function ($post) {
                $post->images->transform(function ($img) {
                    $img->image_url = $img->image_path
                        ? asset('storage/' . $img->image_path)
                        : null;

                    return $img;
                });

                return $post;
            });

        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['images', 'comments'])
            ->findOrFail($id);

        $post->images->transform(function ($img) {
            $img->image_url = $img->image_path
                ? asset('storage/' . $img->image_path)
                : null;

            return $img;
        });

        return response()->json($post, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $post = Post::create([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');

                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json(
            $post->load('images'),
            201
        );
    }

    public function like($id)
    {
        $post = Post::findOrFail($id);

        $post->increment('likes_count');

        return response()->json([
            'message' => 'Me gusta añadido correctamente.',
            'likes_count' => $post->likes_count,
        ], 200);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();

        return response()->json(null, 204);
    }
}