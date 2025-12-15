<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Product",
 *     required={"name", "price", "quantity", "category_id"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="name", type="string", example="Smartphone X"),
 *     @OA\Property(property="description", type="string", example="Latest model with high res camera..."),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="quantity", type="integer", example=50),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="image_url", type="string", example="http://example.com/image.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
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