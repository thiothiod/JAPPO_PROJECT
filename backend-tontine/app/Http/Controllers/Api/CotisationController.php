<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Groupement;
use App\Models\Seance;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CotisationController extends Controller
{
    // GET /api/cotisations
    public function index(Request $request)
    {
        $cotisations = Cotisation::with(['membre', 'groupement', 'seance'])
            ->when($request->groupement_id, fn($q) =>
                $q->where('groupement_id', $request->groupement_id))
            ->when($request->membre_id, fn($q) =>
                $q->where('membre_id', $request->membre_id))
            ->when($request->statut, fn($q) =>
                $q->where('statut', $request->statut))
            ->when($request->seance_id, fn($q) =>
                $q->where('seance_id', $request->seance_id))
            ->orderBy('created_at', 'desc')
            ->get();
            

        return response()->json($cotisations);
    }

    // POST /api/cotisations
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'membre_id'     => 'required|exists:users,id',
            'groupement_id' => 'required|exists:groupements,id',
            'seance_id'     => 'required|exists:seances,id',
            'montant'       => 'required|integer|min:1',
            'note'          => 'nullable|string',
        ]);

        $groupement = Groupement::findOrFail($request->groupement_id);
        $seance     = Seance::findOrFail($request->seance_id);

        // ── Validation 1 : membre actif dans ce groupement ──────────
        $appartient = \App\Models\GroupementMembre::where('membre_id', $request->membre_id)
            ->where('groupement_id', $request->groupement_id)
            ->where('statut', 'actif')
            ->exists();

        if (!$appartient) {
            return response()->json([
                'message' => 'Ce membre n\'appartient pas à ce groupement.'
            ], 422);
        }

        // ── Validation 2 : la séance appartient bien au groupement ──
        if ($seance->groupement_id !== $groupement->id) {
            return response()->json([
                'message' => 'Cette séance n\'appartient pas à ce groupement.'
            ], 422);
        }

        // ── Validation 3 : la séance n'est pas annulée ──────────────
        if ($seance->statut === 'annulee') {
            return response()->json([
                'message' => 'Cette séance a été annulée.'
            ], 422);
        }

        // ── Validation 4 : aujourd'hui = jour fixe du groupement ────
        $aujourdhui = Carbon::today();
        if (!$groupement->estJourCotisation($aujourdhui)) {
            $map = [0=>'dimanche',1=>'lundi',2=>'mardi',3=>'mercredi',
                    4=>'jeudi',5=>'vendredi',6=>'samedi'];
            return response()->json([
                'message' => 'Cotisation impossible aujourd\'hui ('
                             . $map[$aujourdhui->dayOfWeek] . '). '
                             . 'Le jour autorisé est le '
                             . $groupement->jour_cotisation . '.'
            ], 422);
        }

        // ── Validation 5 : date séance = aujourd'hui ────────────────
        if (!$seance->date_seance->isToday() && $seance->date_seance->format('Y-m-d') !== Carbon::today()->format('Y-m-d')) {
            return response()->json([
                'message' => 'Cette seance ne correspond pas à aujourd hui. '
                             . 'Date seance : ' . $seance->date_seance->format('d/m/Y')
            ], 422);
        }

        // ── Validation 6 : pas de doublon pour cette séance ─────────
        $dejaExiste = Cotisation::where('membre_id', $request->membre_id)
            ->where('seance_id', $request->seance_id)
            ->exists();

        if ($dejaExiste) {
            return response()->json([
                'message' => 'Ce membre a dejà cotise pour cette sance.'
            ], 422);
        }

        // ── Validation 7 : montant dans l'intervalle [min, max] ─────
        if ($request->montant < $groupement->montant_min ||
            $request->montant > $groupement->montant_max) {
            return response()->json([
                'message' => 'Montant invalide. '
                             . 'L\'intervalle autorisé est ['
                             . number_format($groupement->montant_min, 0, ',', ' ')
                             . ' – '
                             . number_format($groupement->montant_max, 0, ',', ' ')
                             . '] FCFA.'
            ], 422);
        }

        // ── Enregistrement ───────────────────────────────────────────
        $cotisation = Cotisation::create([
            'membre_id'      => $request->membre_id,
            'groupement_id'  => $request->groupement_id,
            'seance_id'      => $request->seance_id,
            'montant'        => $request->montant,
            'statut'         => 'enregistree',
            'enregistre_par' => Auth::id(),
            'note'           => $request->note,
        ]);

        // Mettre à jour le statut de la séance
        $seance->update(['statut' => 'terminee']);

        // Log
        AuditLog::log(
            'cotisation.create',
            'cotisations',
            $cotisation->id,
            null,
            $cotisation->toArray()
        );

        return response()->json([
            'message'    => 'Cotisation enregistrée avec succès.',
            'cotisation' => $cotisation->load(['membre', 'seance']),
        ], 201);
    }

    // GET /api/cotisations/{id}
    public function show(Cotisation $cotisation)
    {
        return response()->json(
            $cotisation->load(['membre', 'groupement', 'seance'])
        );
    }

    // GET /api/mes-cotisations (pour un membre connecté)
    public function mesCotisations(Request $request)
    {
        $cotisations = Cotisation::with(['groupement', 'seance'])
            ->where('membre_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($cotisations);
    }

    // PUT /api/cotisations/{id}/annuler
    public function annuler(Request $request, Cotisation $cotisation)
    {
        if ($cotisation->statut === 'annulee') {
            return response()->json([
                'message' => 'Cette cotisation est déjà annulée.'
            ], 422);
        }

        $avant = $cotisation->toArray();
        $cotisation->update(['statut' => 'annulee']);

        AuditLog::log(
            'cotisation.annuler',
            'cotisations',
            $cotisation->id,
            $avant,
            $cotisation->fresh()->toArray()
        );

        return response()->json([
            'message' => 'Cotisation annulée.',
        ]);
    }
}