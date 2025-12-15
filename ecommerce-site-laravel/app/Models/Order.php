<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Order",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=101),
 *     @OA\Property(property="client_id", type="integer", example=1),
 *     @OA\Property(property="total_amount", type="number", format="float", example=150.50),
 *     @OA\Property(property="delivery_fee", type="number", format="float", example=10.00),
 *     @OA\Property(property="status", type="string", enum={"PENDING", "CONFIRMED", "PROCESSING", "SHIPPED", "DELIVERED", "CANCELLED"}, example="PENDING"),
 *     @OA\Property(property="payment_status", type="string", enum={"PENDING", "PAID", "FAILED"}, example="PAID"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'client_id',
        'total_amount',
        'delivery_fee',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}