<?php
namespace App\Jobs;

use App\Models\Seance;
use App\Models\Groupement;
use App\Models\GroupementMembre;
use App\Models\Cotisation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarquerSeancesManquantes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $hier = Carbon::yesterday();
        Log::info('Cron séances manquantes — vérification pour : ' . $hier->format('d/m/Y'));

        // Trouver tous les groupements actifs dont hier était le jour de cotisation
        $jourHier = $this->nomJour($hier->dayOfWeek);

        $groupements = Groupement::where('statut', 'actif')
            ->where('jour_cotisation', $jourHier)
            ->get();

        Log::info('Groupements concernés : ' . $groupements->count());

        foreach ($groupements as $groupement) {

            // Trouver la séance d'hier pour ce groupement
            $seance = Seance::where('groupement_id', $groupement->id)
                ->where('date_seance', $hier->toDateString())
                ->first();

            if (!$seance) continue;

            // Trouver tous les membres actifs du groupement
            $membres = GroupementMembre::where('groupement_id', $groupement->id)
                ->where('statut', 'actif')
                ->pluck('membre_id');

            $manquants = 0;

            foreach ($membres as $membreId) {
                // Vérifier si ce membre a cotisé
                $aCotise = Cotisation::where('membre_id', $membreId)
                    ->where('seance_id', $seance->id)
                    ->where('statut', '!=', 'annulee')
                    ->exists();

                if (!$aCotise) {
                    // Créer une cotisation avec statut manquante
                    Cotisation::firstOrCreate(
                        [
                            'membre_id'  => $membreId,
                            'seance_id'  => $seance->id,
                        ],
                        [
                            'groupement_id'  => $groupement->id,
                            'montant'        => 0,
                            'statut'         => 'manquante',
                            'enregistre_par' => null,
                        ]
                    );
                    $manquants++;
                }
            }

            // Mettre à jour le statut de la séance
            $seance->update(['statut' => 'terminee']);

            Log::info("Groupement [{$groupement->nom}] — séance {$seance->numero_seance} : {$manquants} absent(s)");
        }

        Log::info('Cron séances manquantes — terminé.');
    }

    private function nomJour(int $dayOfWeek): string
    {
        return [
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
        ][$dayOfWeek];
    }
}