<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$groupement = \App\Models\Groupement::first();
if ($groupement) {
    \App\Models\GroupementMembre::firstOrCreate([
        'membre_id' => 1,
        'groupement_id' => $groupement->id,
    ], ['statut' => 'actif']);

    \App\Models\Seance::firstOrCreate([
        'groupement_id' => $groupement->id,
        'date_seance' => now()->toDateString(),
    ], ['statut' => 'planifiee']);

    echo "Member and seance added for groupement {$groupement->id}\n";
} else {
    echo "No groupement found\n";
}