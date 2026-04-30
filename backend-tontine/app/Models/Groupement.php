<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Groupement extends Model
{
    protected $fillable = [
        'nom', 'responsable_id', 'montant_min', 'montant_max',
        'jour_cotisation', 'date_debut_cycle', 'date_fin_cycle',
        'taux_interet', 'plafond_pret', 'duree_max_remboursement',
        'approbation_pret', 'statut'
    ];

    protected $casts = [
        'date_debut_cycle' => 'date',
        'date_fin_cycle'   => 'date',
        'taux_interet'     => 'decimal:2',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function membres()
    {
        return $this->hasMany(GroupementMembre::class);
    }

    public function seances()
    {
        return $this->hasMany(Seance::class);
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class);
    }

    public function prets()
    {
        return $this->hasMany(Pret::class);
    }

    // Vérifie si une date correspond au jour fixe du groupement
    public function estJourCotisation(\Carbon\Carbon $date): bool
{
    $jours = [
        'dimanche' => 0,
        'lundi'    => 1,
        'mardi'    => 2,
        'mercredi' => 3,
        'jeudi'    => 4,
        'vendredi' => 5,
        'samedi'   => 6,
    ];
    return $date->dayOfWeek === $jours[$this->jour_cotisation];
}
}