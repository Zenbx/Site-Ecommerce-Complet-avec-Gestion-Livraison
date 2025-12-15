<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="Client",
 *     required={"name", "email", "password", "address"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="address", type="string", example="123 Main St, City"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'clients';

    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mutateur pour hasher automatiquement le mot de passe.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }


    // Relations
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activeCart()
    {
        return $this->hasOne(Cart::class)->where('status', 'ACTIVE');
    }

    /**
     * Scope pour les clients actifs (qui ont passé au moins une commande).
     */
    public function scopeActive($query)
    {
        return $query->has('orders');
    }

    /**
     * Scope pour les clients inactifs (qui n'ont jamais passé de commande).
     */
    public function scopeInactive($query)
    {
        return $query->doesntHave('orders');
    }

    /**
     * Calcule le montant total dépensé par ce client.
     * 
     * @return float
     */
    public function getTotalSpentAttribute()
    {
        return $this->orders()
                    ->where('status', 'DELIVERED')
                    ->sum('total_amount');
    }

    /**
     * Compte le nombre total de commandes de ce client.
     * 
     * @return int
     */
    public function getOrdersCountAttribute()
    {
        return $this->orders()->count();
    }
}