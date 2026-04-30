<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pret extends Model
{
    protected $fillable = [
        'membre_id', 'groupement_id', 'montant',
        'taux_interet', 'montant_interet', 'montant_total_du',
        'statut', 'date_demande', 'date_validation',
        'date_echeance', 'valide_par', 'motif_refus'
    ];

    protected $casts = [
        'date_demande'    => 'date',
        'date_validation' => 'date',
        'date_echeance'   => 'date',
        'taux_interet'    => 'decimal:2',
    ];

    public function membre()
    {
        return $this->belongsTo(User::class, 'membre_id');
    }

    public function groupement()
    {
        return $this->belongsTo(Groupement::class);
    }

    public function remboursements()
    {
        return $this->hasMany(Remboursement::class);
    }

    // Montant total déjà remboursé
    public function totalRembourse(): int
    {
        return $this->remboursements()->sum('montant');
    }

    // Montant encore dû
    public function restantDu(): int
    {
        return $this->montant_total_du - $this->totalRembourse();
    }

    // Pourcentage d'avancement
    public function tauxAvancement(): float
    {
        if ($this->montant_total_du === 0) return 0;
        return round(($this->totalRembourse() / $this->montant_total_du) * 100, 2);
    }
}