<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seance extends Model
{
    protected $fillable = [
        'groupement_id', 'numero_seance',
        'date_seance', 'statut', 'note'
    ];

    protected $casts = [
        'date_seance' => 'date',
    ];

    public function groupement()
    {
        return $this->belongsTo(Groupement::class);
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class);
    }

    // Vérifie si cette séance a une cotisation pour un membre donné
    public function aCotisationPour(int $membreId): bool
    {
        return $this->cotisations()
                    ->where('membre_id', $membreId)
                    ->exists();
    }
}