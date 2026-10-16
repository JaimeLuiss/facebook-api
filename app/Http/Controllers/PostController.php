<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Listar publicaciones con usuarios, imágenes y comentarios.
     *
     * @group Publicaciones
     *
     * @queryParam page integer Número de página. Example: 2
     * @queryParam search string Busca coincidencias parciales en título o contenido. Example: viaje
     *
     * @response 200 {"current_page":1,"data":[{"id":1,"title":"Viaje a Cali","content":"Un día soleado","likes_count":2,"images":[],"comments":[]}],"last_page":1,"per_page":10,"total":1}
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'sometimes|string|max:255',
        ]);

        $search = trim($validated['search'] ?? '');

        $posts = Post::with(['user', 'images', 'comments.user'])
            ->withCount(['likes as actual_likes_count'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'ilike', "%{$search}%")
                        ->orWhere('content', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $posts->getCollection()->each(function ($post): void {
            $post->setAttribute('likes_count', $post->actual_likes_count);
            $post->makeHidden('actual_likes_count');

            $post->images->transform(function ($image) {
                $image->image_url = $image->image_path
                    ? Storage::disk(config('filesystems.image_disk'))->url($image->image_path)
                    : null;

                return $image;
            });
        });

        return response()->json($posts, 200);
    }

    /**
     * Consultar una publicación específica.
     *
     * @group Publicaciones
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 {"id":1,"title":"Viaje a Cali","content":"Un día soleado","likes_count":2,"images":[],"comments":[]}
     */
    public function show($id)
    {
        $post = Post::with([
            'user',
            'images',
            'comments.user',
        ])->withCount(['likes as actual_likes_count'])->findOrFail($id);

        $post->setAttribute('likes_count', $post->actual_likes_count);
        $post->makeHidden('actual_likes_count');

        $post->images->transform(function ($image) {
            $image->image_url = $image->image_path
                ? Storage::disk(config('filesystems.image_disk'))->url($image->image_path)
                : null;

            return $image;
        });

        return response()->json($post, 200);
    }

    /**
     * Crear una publicación asociada al usuario autenticado.
     *
     * @group Publicaciones
     *
     * @authenticated
     *
     * @bodyParam title string required Título de la publicación. Example: Mi viaje
     * @bodyParam content string required Contenido de la publicación. Example: Un día excelente
     * @bodyParam images[] file Imágenes opcionales en formato JPEG, PNG, GIF o WEBP (máximo 2 MB cada una).
     *
     * @response 201 {"id":1,"title":"Mi viaje","content":"Un día excelente","likes_count":0,"user_id":1,"images":[]}
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
                $disk = config('filesystems.image_disk');
                $path = $file->store('posts', $disk);

                if ($path === false) {
                    throw new \RuntimeException('No se pudo guardar la imagen subida.');
                }

                $post->images()->create([
                    'image_path' => $path,
                ]);
            }
        }

        // Incluir el usuario y las imágenes en la respuesta.
        $post->load(['user', 'images']);

        $post->images->transform(function ($image) {
            $image->image_url = $image->image_path
                ? Storage::disk(config('filesystems.image_disk'))->url($image->image_path)
                : null;

            return $image;
        });

        return response()->json($post, 201);
    }

    /**
     * Incrementar el contador de Me gusta.
     *
     * @group Publicaciones
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 {"message":"Me gusta añadido correctamente.","likes_count":1}
     */
    public function like(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $now = now();
        $inserted = DB::table('post_user')->insertOrIgnore([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted > 0) {
            $post->increment('likes_count');
        }

        return response()->json([
            'message' => $inserted > 0
                ? 'Me gusta añadido correctamente.'
                : 'Ya habías indicado que te gusta esta publicación.',
            'likes_count' => $post->likes()->count(),
        ], 200);
    }

    /**
     * Eliminar una publicación y sus imágenes físicas.
     *
     * @group Publicaciones
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 204
     */
    public function destroy($id)
    {
        $post = Post::with('images')->findOrFail($id);
        $disk = Storage::disk(config('filesystems.image_disk'));

        foreach ($post->images as $image) {
            $disk->delete($image->image_path);
        }

        $post->delete();

        return response()->json(null, 204);
    }
}
