<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="CartLine",
 *     type="object",
 *     title="Cart Line",
 *     description="Représente une ligne du panier",
 *     required={"id", "cart_id", "product_id", "quantity", "unit_price"},
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         example=1
 *     ),
 *
 *     @OA\Property(
 *         property="cart_id",
 *         type="integer",
 *         format="int64",
 *         example=10
 *     ),
 *
 *     @OA\Property(
 *         property="product_id",
 *         type="integer",
 *         format="int64",
 *         example=42
 *     ),
 *
 *     @OA\Property(
 *         property="quantity",
 *         type="integer",
 *         minimum=1,
 *         example=2
 *     ),
 *
 *     @OA\Property(
 *         property="unit_price",
 *         type="number",
 *         format="float",
 *         example=19.99
 *     ),
 *
 *     @OA\Property(
 *         property="subtotal",
 *         type="number",
 *         format="float",
 *         description="quantity * unit_price",
 *         example=39.98,
 *         readOnly=true
 *     ),
 *
 *     @OA\Property(
 *         property="added_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2025-01-10T14:32:00Z"
 *     )
 * )
 */

class CartLine extends Model
{
    use HasFactory;

    protected $table = 'cart_lines';

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'added_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relations
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Méthodes utilitaires
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}