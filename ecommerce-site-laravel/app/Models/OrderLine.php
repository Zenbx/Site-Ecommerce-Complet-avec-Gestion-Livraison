<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="OrderLine",
 *     type="object",
 *     title="Order Line",
 *     description="Représente une ligne de commande",
 *     required={"id", "order_id", "product_id", "quantity", "unit_price"},
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         example=1
 *     ),
 *
 *     @OA\Property(
 *         property="order_id",
 *         type="integer",
 *         format="int64",
 *         example=1001
 *     ),
 *
 *     @OA\Property(
 *         property="product_id",
 *         type="integer",
 *         format="int64",
 *         example=45
 *     ),
 *
 *     @OA\Property(
 *         property="quantity",
 *         type="integer",
 *         minimum=1,
 *         example=3
 *     ),
 *
 *     @OA\Property(
 *         property="unit_price",
 *         type="number",
 *         format="float",
 *         example=15000.00
 *     ),
 *
 *     @OA\Property(
 *         property="subtotal",
 *         type="number",
 *         format="float",
 *         description="quantity * unit_price",
 *         example=45000.00,
 *         readOnly=true
 *     )
 * )
 */

class OrderLine extends Model
{
    use HasFactory;

    protected $table = 'order_lines';

    protected $fillable = [
        'order_id',
        'product_id',
        'unit_price',
        'quantity',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public $timestamps = false;

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
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