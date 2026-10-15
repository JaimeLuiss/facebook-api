<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Listar publicaciones con usuarios, imágenes y comentarios.
     */
    public function index()
    {
        $posts = Post::with([
            'user',
            'images',
            'comments.user',
        ])
            ->latest()
            ->get();

        $posts->each(function ($post) {
            $post->images->transform(function ($image) {
                $image->image_url = $image->image_path
                    ? asset('storage/' . $image->image_path)
                    : null;

                return $image;
            });
        });

        return response()->json($posts, 200);
    }

    /**
     * Consultar una publicación específica.
     */
    public function show($id)
    {
        $post = Post::with([
            'user',
            'images',
            'comments.user',
        ])->findOrFail($id);

        $post->images->transform(function ($image) {
            $image->image_url = $image->image_path
                ? asset('storage/' . $image->image_path)
                : null;

            return $image;
        });

        return response()->json($post, 200);
    }

    /**
     * Crear una publicación asociada al usuario autenticado.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // La ruta exige autenticación mediante Sanctum.
        $user = $request->user();

        // Crear la publicación.
        $post = new Post([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        // Asociar explícitamente la publicación al usuario autenticado.
        $post->user()->associate($user);
        $post->save();

        // Guardar las imágenes y registrar sus rutas.
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');

                $post->images()->create([
                    'image_path' => $path,
                ]);
            }
        }

        // Incluir el usuario y las imágenes en la respuesta.
        $post->load(['user', 'images']);

        $post->images->transform(function ($image) {
            $image->image_url = $image->image_path
                ? asset('storage/' . $image->image_path)
                : null;

            return $image;
        });

        return response()->json($post, 201);
    }

    /**
     * Incrementar el contador de Me gusta.
     */
    public function like($id)
    {
        $post = Post::findOrFail($id);

        $post->increment('likes_count');

        return response()->json([
            'message' => 'Me gusta añadido correctamente.',
            'likes_count' => $post->likes_count,
        ], 200);
    }

    /**
     * Eliminar una publicación y sus imágenes físicas.
     */
    public function destroy($id)
    {
        $post = Post::with('images')->findOrFail($id);

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();

        return response()->json(null, 204);
    }
}