<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remboursement extends Model
{
    protected $fillable = [
        'pret_id', 'montant', 'type',
        'date_remboursement', 'enregistre_par', 'note'
    ];

    protected $casts = [
        'date_remboursement' => 'date',
    ];

    public function pret()
    {
        return $this->belongsTo(Pret::class);
    }

    public function enregistrePar()
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}