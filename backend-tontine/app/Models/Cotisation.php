<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotisation extends Model
{
    protected $fillable = [
        'membre_id', 'groupement_id', 'seance_id',
        'montant', 'statut', 'enregistre_par', 'note'
    ];

    public function membre()
    {
        return $this->belongsTo(User::class, 'membre_id');
    }

    public function groupement()
    {
        return $this->belongsTo(Groupement::class);
    }

    public function seance()
    {
        return $this->belongsTo(Seance::class);
    }

    public function enregistrePar()
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}