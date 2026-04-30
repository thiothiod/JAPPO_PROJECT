<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $fillable = [
        'nom', 'telephone', 'password',
        'role', 'actif', 'adresse',
        'date_naissance', 'photo'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'actif' => 'boolean',
        'date_naissance' => 'date',
    ];

    // Un user peut être responsable de plusieurs groupements
    public function groupementsResponsable()
    {
        return $this->hasMany(Groupement::class, 'responsable_id');
    }

    // Le groupement auquel ce membre appartient (1 seul)
    public function groupement()
    {
        return $this->hasOne(GroupementMembre::class, 'membre_id');
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class, 'membre_id');
    }

    public function prets()
    {
        return $this->hasMany(Pret::class, 'membre_id');
    }
}
