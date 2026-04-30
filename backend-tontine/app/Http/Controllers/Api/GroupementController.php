<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Groupement;
use App\Models\GroupementMembre;
use App\Models\Seance;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GroupementController extends Controller
{
    // GET /api/groupements
    public function index()
    {
        $groupements = Groupement::with('responsable', 'membres')
            ->withCount('membres')
            ->get();

        return response()->json($groupements);
    }

    // POST /api/groupements
    public function store(Request $request)
    {
        $request->validate([
            'nom'                      => 'required|string|unique:groupements',
            'responsable_id'           => 'required|exists:users,id',
            'montant_min'              => 'required|integer|min:1',
            'montant_max'              => 'required|integer|gt:montant_min',
            'jour_cotisation'          => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'date_debut_cycle'         => 'required|date',
            'taux_interet'             => 'required|numeric|min:0',
            'plafond_pret'             => 'nullable|integer|min:1',
            'duree_max_remboursement'  => 'nullable|integer|min:1',
            'approbation_pret'         => 'nullable|in:responsable_seul,vote_groupe,admin_uniquement',
        ]);

        // Vérifier que date_debut_cycle tombe bien sur le jour_cotisation
        // Carbon est une bibliothèque de manipulation de dates très utilisée en PHP, elle facilite les calculs et les comparaisons de dates

        $dateDebut = Carbon::parse($request->date_debut_cycle);
        if (!$this->estBonJour($dateDebut, $request->jour_cotisation)) {
            return response()->json([
                'message' => 'La date de début doit correspondre au jour de cotisation choisi ('
                             . $request->jour_cotisation . ').'
            ], 422);
        }

        // Calculer date_fin_cycle automatiquement
        // Un cycle de 52 semaines = 364 jours
        // copy() est utilisé pour ne pas modifier $dateDebut lors de l'ajout de jours
        $dateFinCycle = $dateDebut->copy()->addDays(364);

        $groupement = Groupement::create([
            ...$request->all(),
            'date_fin_cycle' => $dateFinCycle->toDateString(),
            'statut'         => 'actif',
        ]);

        // Générer automatiquement les 52 séances
        $this->genererSeances($groupement, $dateDebut);

        // Logger l'action
        AuditLog::log(
            'groupement.create',
            'groupements',
            $groupement->id,
            null,
            $groupement->toArray()
        );

        return response()->json([
            'message'    => 'Groupement créé avec ses 52 séances.',
            'groupement' => $groupement->load('seances'),
        ], 201);
    }

    // GET /api/groupements/{id}
    public function show(Groupement $groupement)
    {
        return response()->json(
            $groupement->load([
                'responsable',
                'membres.membre',
                'seances' => fn($q) => $q->orderBy('numero_seance'),
            ])
        );
    }

    // PUT /api/groupements/{id}
    public function update(Request $request, Groupement $groupement)
    {
        $avant = $groupement->toArray();

        $request->validate([
            'nom'                     => 'sometimes|string|unique:groupements,nom,'.$groupement->id,
            'montant_min'             => 'sometimes|integer|min:1',
            'montant_max'             => 'sometimes|integer|gt:montant_min',
            'taux_interet'            => 'sometimes|numeric|min:0',
            'plafond_pret'            => 'nullable|integer|min:1',
            'duree_max_remboursement' => 'nullable|integer|min:1',
            'statut'                  => 'sometimes|in:actif,inactif,archive',
        ]);

        $groupement->update($request->all());

        AuditLog::log(
            'groupement.update',
            'groupements',
            $groupement->id,
            $avant,
            $groupement->fresh()->toArray()
        );

        return response()->json([
            'message'    => 'Groupement mis à jour.',
            'groupement' => $groupement,
        ]);
    }

    // DELETE /api/groupements/{id}
    public function destroy(Groupement $groupement)
    {
        // Vérifier qu'il n'y a pas de prêts en cours
        $pretsEnCours = $groupement->prets()
            ->whereIn('statut', ['en_attente', 'valide', 'en_cours'])
            ->count();

        if ($pretsEnCours > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : '
                             . $pretsEnCours . ' prêt(s) en cours.'
            ], 422);
        }

        AuditLog::log(
            'groupement.delete',
            'groupements',
            $groupement->id,
            $groupement->toArray(),
            null
        );

        $groupement->update(['statut' => 'archive']); // soft delete logique

        return response()->json([
            'message' => 'Groupement archivé avec succès.'
        ]);
    }

    // ─── Méthodes privées ────────────────────────────────────────────

    // Génère les 52 séances hebdomadaires du cycle
    private function genererSeances(Groupement $groupement, Carbon $dateDebut): void
    //carbon permet de manipuler les dates facilement, on peut faire des opérations dessus comme addWeek() pour ajouter une semaine
    {
        $seances = [];
        $date    = $dateDebut->copy();

        for ($i = 1; $i <= 52; $i++) {
            $seances[] = [
                'groupement_id' => $groupement->id,
                'numero_seance' => $i,
                'date_seance'   => $date->toDateString(),
                'statut'        => 'a_venir',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
            $date->addWeek(); // +7 jours à chaque itération
        }

        Seance::insert($seances); // insert groupé = 1 seule requête SQL
    }

    // Vérifie que la date correspond au bon jour de la semaine
   private function estBonJour(Carbon $date, string $jour): bool
{
    $map = [
        'dimanche' => 0,
        'lundi'    => 1,
        'mardi'    => 2,
        'mercredi' => 3,
        'jeudi'    => 4,
        'vendredi' => 5,
        'samedi'   => 6,
    ];
    return $date->dayOfWeek === $map[$jour];
}
}