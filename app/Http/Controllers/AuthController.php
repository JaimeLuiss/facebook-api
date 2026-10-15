<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar un usuario y generar su token.
     *
     * @group Autenticación
     *
     * @bodyParam name string required Nombre del usuario. Example: Carlos Gomez
     * @bodyParam email string required Correo único del usuario. Example: carlos@example.com
     * @bodyParam password string required Contraseña de al menos 8 caracteres. Example: password123
     *
     * @response 201 {"message":"Usuario registrado correctamente.","user":{"id":1,"name":"Carlos Gomez","email":"carlos@example.com"},"token":"1|token"}
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Iniciar sesión y generar un token.
     *
     * @group Autenticación
     *
     * @bodyParam email string required Correo registrado. Example: carlos@example.com
     * @bodyParam password string required Contraseña de la cuenta. Example: password123
     *
     * @response 200 {"message":"Inicio de sesión exitoso.","user":{"id":1,"name":"Carlos Gomez","email":"carlos@example.com"},"token":"1|token"}
     * @response 422 {"message":"Las credenciales son incorrectas.","errors":{"email":["Las credenciales son incorrectas."]}}
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Cerrar sesión y revocar el token actual.
     *
     * @group Autenticación
     *
     * @authenticated
     *
     * @response 200 {"message":"Sesión cerrada correctamente."}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }
}
