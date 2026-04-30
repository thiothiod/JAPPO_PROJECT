<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false; // on gère created_at manuellement

    protected $fillable = [
        'utilisateur_id', 'action', 'entite',
        'entite_id', 'donnees_avant', 'donnees_apres',
        'ip', 'created_at'
    ];

    protected $casts = [
        'donnees_avant' => 'array',
        'donnees_apres' => 'array',
        'created_at'    => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    // Méthode statique pour logger facilement partout
    public static function log(
        string $action,
        string $entite,
        EloquentModel|int|null $entiteId = null,
        ?array $avant = null,
        ?array $apres = null
    ): void {
        static::create([
            'utilisateur_id' => Auth::id(),
            'action'         => $action,
            'entite'         => $entite,
            'entite_id'      => $entiteId instanceof EloquentModel ? $entiteId->getKey() : $entiteId,
            'donnees_avant'  => $avant,
            'donnees_apres'  => $apres,
            'ip'             => request()->ip(),
            'created_at'     => now(),
        ]);
    }
}