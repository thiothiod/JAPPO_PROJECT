<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remboursement;
use App\Models\Pret;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RemboursementController extends Controller
{
    // GET /api/remboursements
    public function index(Request $request)
    {
        $remboursements = Remboursement::with(['pret.membre', 'pret.groupement'])
            ->when($request->pret_id, fn($q) =>
                $q->where('pret_id', $request->pret_id))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($remboursements);
    }

    // POST /api/remboursements
    public function store(Request $request)
    {
        $request->validate([
            'pret_id'             => 'required|exists:prets,id',
            'montant'             => 'required|integer|min:1',
            'date_remboursement'  => 'required|date',
            'note'                => 'nullable|string',
        ]);

        $pret = Pret::findOrFail($request->pret_id);

        // Validation 1 : prêt en cours
        if (!in_array($pret->statut, ['valide', 'en_cours'])) {
            return response()->json([
                'message' => 'Ce prêt ne peut pas être remboursé (statut: ' . $pret->statut . ').'
            ], 422);
        }

        // Validation 2 : montant ne dépasse pas le restant dû
        $restantDu = $pret->restantDu();

        if ($request->montant > $restantDu) {
            return response()->json([
                'message' => 'Montant dépasse le restant dû ('
                             . number_format($restantDu, 0, ',', ' ')
                             . ' FCFA).'
            ], 422);
        }

        // Déterminer le type : total ou partiel
        $type = ($request->montant == $restantDu) ? 'total' : 'partiel';

        $remboursement = Remboursement::create([
            'pret_id'            => $request->pret_id,
            'montant'            => $request->montant,
            'type'               => $type,
            'date_remboursement' => $request->date_remboursement,
            'enregistre_par'     => auth()->id(),
            'note'               => $request->note,
        ]);

        // Mettre à jour le statut du prêt si soldé
        $nouveauRestant = $pret->restantDu();
        if ($nouveauRestant <= 0) {
            $pret->update(['statut' => 'rembourse']);
        } elseif ($pret->statut === 'valide') {
            $pret->update(['statut' => 'en_cours']);
        }

        AuditLog::log(
            'remboursement.create',
            'remboursements',
            $remboursement->id,
            null,
            $remboursement->toArray()
        );

        return response()->json([
            'message'        => 'Remboursement enregistré.',
            'remboursement'  => $remboursement,
            'restant_du'     => $pret->fresh()->restantDu(),
            'pret_statut'    => $pret->fresh()->statut,
        ], 201);
    }

    // GET /api/remboursements/{id}
    public function show(Remboursement $remboursement)
    {
        return response()->json(
            $remboursement->load(['pret.membre', 'pret.groupement'])
        );
    }
}