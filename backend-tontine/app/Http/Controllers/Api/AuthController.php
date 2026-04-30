<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|min:3',
            'telephone' => 'required|string|unique:users',
            'password'  => 'required|min:6|confirmed',
            'role'      => 'sometimes|in:administrateur,responsable,membre',
        ]);

        $user = User::create([
            'nom'       => $request->nom,
            'telephone' => $request->telephone,
            'password'  => Hash::make($request->password),
            'role'      => $request->role ?? 'membre',
            'actif'     => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'telephone' => $user->telephone,
                'role'      => $user->role,
                'actif'     => $user->actif,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'password'  => 'required',
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'telephone' => ['Numéro de téléphone ou mot de passe incorrect.']
            ]);
        }

        if (!$user->actif) {
            return response()->json([
                'message' => 'Compte désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'telephone' => $user->telephone,
                'role'      => $user->role,
                'actif'     => $user->actif,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'id'        => $request->user()->id,
            'nom'       => $request->user()->nom,
            'telephone' => $request->user()->telephone,
            'role'      => $request->user()->role,
            'actif'     => $request->user()->actif,
        ]);
    }
}