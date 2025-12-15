<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'quantity',
        'price',
        'serial_id',
        'description',
        'brand',
        'category_id',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relations
     public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartLines()
    {
        return $this->hasMany(CartLine::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}