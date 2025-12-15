<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Représente un paiement associé à une commande",
 *     required={"id", "order_id", "payment_method", "amount", "transaction_reference"},
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
 *         property="payment_method",
 *         type="string",
 *         description="Méthode de paiement utilisée",
 *         example="mobile_money"
 *     ),
 *
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         example=75000.00
 *     ),
 *
 *     @OA\Property(
 *         property="transaction_reference",
 *         type="string",
 *         description="Référence unique de la transaction",
 *         example="TXN-2025-000045"
 *     ),
 *
 *     @OA\Property(
 *         property="paid_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2025-01-15T14:32:10Z"
 *     ),
 *
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2025-01-15T14:30:00Z"
 *     )
 * )
 */

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'transaction_reference',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}