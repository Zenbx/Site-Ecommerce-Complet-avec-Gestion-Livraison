<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Cart",
 *     required={"client_id"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=10),
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", enum={"ACTIVE", "ABANDONED", "CONVERTED"}, example="ACTIVE"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'client_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function cartLines()
    {
        return $this->hasMany(CartLine::class);
    }

    // Méthodes utilitaires
    public function getTotalAttribute()
    {
        return $this->cartLines->sum(function ($line) {
            return $line->quantity * $line->unit_price;
        });
    }

    public function getItemsCountAttribute()
    {
        return $this->cartLines->sum('quantity');
    }
}