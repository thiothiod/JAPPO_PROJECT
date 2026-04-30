<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupementMembre extends Model
{
    protected $table = 'groupement_membres';

    protected $fillable = [
        'membre_id', 'groupement_id',
        'date_adhesion', 'statut'
    ];

    protected $casts = [
        'date_adhesion' => 'date',
    ];

    public function membre()
    {
        return $this->belongsTo(User::class, 'membre_id');
    }

    public function groupement()
    {
        return $this->belongsTo(Groupement::class);
    }
}