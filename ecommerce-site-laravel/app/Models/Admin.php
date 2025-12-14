<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
/**
     * Mutateur pour l'attribut password.
     * 
     * Cette méthode est appelée automatiquement chaque fois que vous
     * assignez une valeur à $admin->password. Elle hashe automatiquement
     * le mot de passe en clair avant de le stocker en base de données.
     * 
     * Grâce à ce mutateur, vous pouvez écrire simplement :
     * $admin->password = 'monMotDePasse';
     * Et le mot de passe sera automatiquement hashé avec bcrypt.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Accesseur pour l'attribut role.
     * 
     * Retourne le rôle en majuscules pour garantir la cohérence.
     * Même si en base de données c'est stocké en majuscules grâce à l'ENUM,
     * cet accesseur garantit que même si quelque chose change, le rôle
     * sera toujours retourné en majuscules.
     */
    public function getRoleAttribute($value)
    {
        return strtoupper($value);
    }

    /**
     * Scope pour filtrer les admins par rôle.
     * 
     * Les scopes sont des méthodes réutilisables qui ajoutent des contraintes
     * aux requêtes. Vous pouvez les utiliser comme ceci :
     * Admin::role('SUPERVISEUR')->get()
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role Le rôle à filtrer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', strtoupper($role));
    }

    /**
     * Scope pour récupérer seulement les gestionnaires.
     * 
     * Usage : Admin::gestionnaires()->get()
     */
    public function scopeGestionnaires($query)
    {
        return $query->where('role', 'GESTIONNAIRE');
    }

    /**
     * Scope pour récupérer seulement les superviseurs.
     * 
     * Usage : Admin::superviseurs()->get()
     */
    public function scopeSuperviseurs($query)
    {
        return $query->where('role', 'SUPERVISEUR');
    }

    /**
     * Vérifie si cet admin est un administrateur complet.
     * 
     * Cette méthode helper vous permet d'écrire du code plus lisible :
     * if ($admin->isAdmin()) { ... }
     * plutôt que
     * if ($admin->role === 'ADMIN') { ... }
     */
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    /**
     * Vérifie si cet admin est un superviseur.
     */
    public function isSuperviseur(): bool
    {
        return $this->role === 'SUPERVISEUR';
    }

    /**
     * Vérifie si cet admin est un gestionnaire.
     */
    public function isGestionnaire(): bool
    {
        return $this->role === 'GESTIONNAIRE';
    }
}