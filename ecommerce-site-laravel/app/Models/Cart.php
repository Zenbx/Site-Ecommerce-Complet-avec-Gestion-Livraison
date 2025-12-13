<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'cart';

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