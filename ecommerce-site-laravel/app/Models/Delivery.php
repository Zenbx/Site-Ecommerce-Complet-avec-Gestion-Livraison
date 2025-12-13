<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $table = 'delivery';

    protected $fillable = [
        'order_id',
        'delivery_person_id',
        'delivery_address',
        'status',
        'tracking_code',
        'delivered_at',
        'qr_token',
        'qr_expires_at',
        'qr_scanned_at',
        'qr_status',
        'confirmation_img_url',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'qr_expires_at' => 'datetime',
        'qr_scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryPerson()
    {
        return $this->belongsTo(DeliveryPerson::class);
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