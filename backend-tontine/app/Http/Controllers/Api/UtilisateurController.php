<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    // GET /api/utilisateurs
    public function index(Request $request)
    {
        $utilisateurs = User::with('groupement.groupement')
            ->when($request->role, fn($q) =>
                $q->where('role', $request->role))
            ->when($request->actif !== null, fn($q) =>
                $q->where('actif', $request->actif))
            ->when($request->search, fn($q) =>
                $q->where('nom', 'ilike', '%' . $request->search . '%')
                  ->orWhere('telephone', 'like', '%' . $request->search . '%'))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($utilisateurs);
    }

    // GET /api/utilisateurs/{id}
    public function show(User $utilisateur)
    {
        return response()->json(
            $utilisateur->load([
                'groupement.groupement',
                'cotisations',
                'prets.remboursements',
            ])
        );
    }

    // POST /api/utilisateurs
    public function store(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|min:3',
            'telephone' => 'required|string|unique:users',
            'password'  => 'required|min:6',
            'role'      => 'required|in:administrateur,responsable,membre',
            'adresse'   => 'nullable|string',
            'date_naissance' => 'nullable|date',
        ]);

        $utilisateur = User::create([
            'nom'            => $request->nom,
            'telephone'      => $request->telephone,
            'password'       => Hash::make($request->password),
            'role'           => $request->role,
            'actif'          => true,
            'adresse'        => $request->adresse,
            'date_naissance' => $request->date_naissance,
        ]);

        AuditLog::log(
            'utilisateur.create',
            'users',
            $utilisateur->id,
            null,
            $utilisateur->makeHidden('password')->toArray()
        );

        return response()->json([
            'message'     => 'Utilisateur créé avec succès.',
            'utilisateur' => $utilisateur->makeHidden('password'),
        ], 201);
    }

    // PUT /api/utilisateurs/{id}
    public function update(Request $request, User $utilisateur)
    {
        $avant = $utilisateur->makeHidden('password')->toArray();

        $request->validate([
            'nom'       => 'sometimes|string|min:3',
            'telephone' => 'sometimes|string|unique:users,telephone,' . $utilisateur->id,
            'role'      => 'sometimes|in:administrateur,responsable,membre',
            'adresse'   => 'nullable|string',
            'date_naissance' => 'nullable|date',
            'password'  => 'sometimes|min:6',
        ]);

        $data = $request->except('password');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $utilisateur->update($data);

        AuditLog::log(
            'utilisateur.update',
            'users',
            $utilisateur->id,
            $avant,
            $utilisateur->fresh()->makeHidden('password')->toArray()
        );

        return response()->json([
            'message'     => 'Utilisateur mis à jour.',
            'utilisateur' => $utilisateur->fresh()->makeHidden('password'),
        ]);
    }

    // PUT /api/utilisateurs/{id}/activer
    public function activer(User $utilisateur)
    {
        if ($utilisateur->actif) {
            return response()->json([
                'message' => 'Ce compte est déjà actif.'
            ], 422);
        }

        $avant = $utilisateur->toArray();
        $utilisateur->update(['actif' => true]);

        AuditLog::log(
            'utilisateur.activer',
            'users',
            $utilisateur->id,
            $avant,
            $utilisateur->fresh()->toArray()
        );

        return response()->json([
            'message'     => 'Compte activé avec succès.',
            'utilisateur' => $utilisateur->fresh()->makeHidden('password'),
        ]);
    }

    // PUT /api/utilisateurs/{id}/desactiver
    public function desactiver(User $utilisateur)
    {
        if (!$utilisateur->actif) {
            return response()->json([
                'message' => 'Ce compte est deja desactive.'
            ], 422);
        }

        // Empêcher de désactiver le dernier admin
        if ($utilisateur->role === 'administrateur') {
            $nbAdmins = User::where('role', 'administrateur')
                ->where('actif', true)
                ->count();

            if ($nbAdmins <= 1) {
                return response()->json([
                    'message' => 'Impossible de desactiver le dernier administrateur.'
                ], 422);
            }
        }

        $avant = $utilisateur->toArray();
        $utilisateur->update(['actif' => false]);

        AuditLog::log(
            'utilisateur.desactiver',
            'users',
            $utilisateur->id,
            $avant,
            $utilisateur->fresh()->toArray()
        );

        return response()->json([
            'message'     => 'Compte desactive.',
            'utilisateur' => $utilisateur->fresh()->makeHidden('password'),
        ]);
    }

    // DELETE /api/utilisateurs/{id} — suppression logique
    public function destroy(User $utilisateur)
    {
        // Vérifier qu'il n'a pas de prêts en cours
        $pretsEnCours = $utilisateur->prets()
            ->whereIn('statut', ['en_attente', 'valide', 'en_cours'])
            ->count();

        if ($pretsEnCours > 0) {
            return response()->json([
                'message' => 'Impossible : cet utilisateur a '
                             . $pretsEnCours . ' prêt(s) en cours.'
            ], 422);
        }

        AuditLog::log(
            'utilisateur.delete',
            'users',
            $utilisateur->id,
            $utilisateur->makeHidden('password')->toArray(),
            null
        );

        // Soft delete — désactiver seulement
        $utilisateur->update(['actif' => false]);

        return response()->json([
            'message' => 'Utilisateur desactive (suppression logique).'
        ]);
    }
}