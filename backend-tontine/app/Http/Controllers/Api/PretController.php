<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pret;
use App\Models\Cotisation;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PretController extends Controller
{
    // GET /api/prets
    public function index(Request $request)
    {
        $prets = Pret::with(['membre', 'groupement', 'remboursements'])
            ->when($request->groupement_id, fn($q) =>
            //le fn permet d'écrire une fonction anonyme plus concise. $q représente la requête en cours de construction.
                $q->where('groupement_id', $request->groupement_id))
            ->when($request->membre_id, fn($q) =>
                $q->where('membre_id', $request->membre_id))
            ->when($request->statut, fn($q) =>
                $q->where('statut', $request->statut))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prets);
    }

    // POST /api/prets — demande de prêt
    public function store(Request $request)
    {
        $request->validate([
            'membre_id'     => 'required|exists:users,id',
            'groupement_id' => 'required|exists:groupements,id',
            'montant'       => 'required|integer|min:1',
        ]);

        $groupement = \App\Models\Groupement::findOrFail($request->groupement_id);

        // Validation 1 : membre actif dans ce groupement
        $appartient = \App\Models\GroupementMembre::where('membre_id', $request->membre_id)
            ->where('groupement_id', $request->groupement_id)
            ->where('statut', 'actif')
            ->exists();

        if (!$appartient) {
            return response()->json([
                'message' => 'Ce membre n\'appartient pas à ce groupement.'
            ], 422);
        }

        // Validation 2 : le membre a déjà cotisé au moins une fois
        $aCotise = Cotisation::where('membre_id', $request->membre_id)
            ->where('groupement_id', $request->groupement_id)
            ->where('statut', '!=', 'annulee')
            ->exists();

        if (!$aCotise) {
            return response()->json([
                'message' => 'Ce membre doit avoir cotisé au moins une fois avant de demander un prêt.'
            ], 422);
        }

        // Validation 3 : pas de prêt en cours
        $pretEnCours = Pret::where('membre_id', $request->membre_id)
            ->where('groupement_id', $request->groupement_id)
            ->whereIn('statut', ['en_attente', 'valide', 'en_cours'])
            ->exists();

        if ($pretEnCours) {
            return response()->json([
                'message' => 'Ce membre a dejà un pret en cours dans ce groupement.'
            ], 422);
        }

        // Validation 4 : montant ne dépasse pas le plafond
        if ($groupement->plafond_pret && $request->montant > $groupement->plafond_pret) {
            return response()->json([
                'message' => 'Montant demandé depasse le plafond autorise de '
                             . number_format($groupement->plafond_pret, 0, ',', ' ')
                             . ' FCFA.'
            ], 422);
        }

        // Validation 5 : montant ne dépasse pas le total cotisé
        $totalCotise = Cotisation::where('membre_id', $request->membre_id)
            ->where('groupement_id', $request->groupement_id)
            ->where('statut', '!=', 'annulee')
            ->sum('montant');

        if ($request->montant > $totalCotise) {
            return response()->json([
                'message' => 'Montant demandé ('
                             . number_format($request->montant, 0, ',', ' ')
                             . ' FCFA) dépasse votre total cotisé ('
                             . number_format($totalCotise, 0, ',', ' ')
                             . ' FCFA).'
            ], 422);
        }

        // Calcul automatique des intérêts
        $taux            = $groupement->taux_interet;
        $montantInteret  = (int) round($request->montant * ($taux / 100));
        $montantTotalDu  = $request->montant + $montantInteret;

        $pret = Pret::create([
            'membre_id'       => $request->membre_id,
            'groupement_id'   => $request->groupement_id,
            'montant'         => $request->montant,
            'taux_interet'    => $taux,
            'montant_interet' => $montantInteret,
            'montant_total_du'=> $montantTotalDu,
            'statut'          => 'en_attente',
            'date_demande'    => Carbon::today(),
        ]);

        AuditLog::log('pret.create', 'prets', $pret->id, null, $pret->toArray());

        return response()->json([
            'message' => 'Demande de prêt enregistrée.',
            'pret'    => $pret->load('membre', 'groupement'),
        ], 201);
    }

    // GET /api/prets/{id}
    public function show(Pret $pret)
    {
        return response()->json(
            $pret->load(['membre', 'groupement', 'remboursements'])
        );
    }

    // PUT /api/prets/{id}/valider
    public function valider(Request $request, Pret $pret)
    {
        if ($pret->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Ce prêt ne peut pas être validé (statut: ' . $pret->statut . ').'
            ], 422);
        }

        $request->validate([
            'date_echeance' => 'required|date|after:today',
        ]);

        $avant = $pret->toArray();
        $pret->update([
            'statut'          => 'en_cours',
            'date_validation' => Carbon::today(),
            'date_echeance'   => $request->date_echeance,
            'valide_par'      => auth()->id(),
        ]);

        AuditLog::log('pret.valider', 'prets', $pret->id, $avant, $pret->fresh()->toArray());

        return response()->json([
            'message' => 'Prêt validé avec succès.',
            'pret'    => $pret->load('membre'),
        ]);
    }

    // PUT /api/prets/{id}/refuser
    public function refuser(Request $request, Pret $pret)
    {
        if ($pret->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Ce prêt ne peut pas être refusé (statut: ' . $pret->statut . ').'
            ], 422);
        }

        $request->validate([
            'motif_refus' => 'required|string|min:5',
        ]);

        $avant = $pret->toArray();
        $pret->update([
            'statut'      => 'refuse',
            'motif_refus' => $request->motif_refus,
            'valide_par'  => auth()->id(),
        ]);

        AuditLog::log('pret.refuser', 'prets', $pret->id, $avant, $pret->fresh()->toArray());

        return response()->json([
            'message' => 'Prêt refusé.',
            'pret'    => $pret,
        ]);
    }
}