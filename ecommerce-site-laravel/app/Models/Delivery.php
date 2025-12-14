<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $table = 'deliveries';

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

    /**
 * Vérifie si la livraison est en attente d'assignation.
 */
public function isPending(): bool
{
    return $this->status === 'PENDING';
}

/**
 * Vérifie si la livraison a été assignée à un livreur.
 */
public function isAssigned(): bool
{
    return $this->status === 'ASSIGNED';
}

/**
 * Vérifie si la livraison est en cours.
 */
public function isInTransit(): bool
{
    return $this->status === 'IN_TRANSIT';
}

/**
 * Vérifie si la livraison a été complétée avec succès.
 */
public function isDelivered(): bool
{
    return $this->status === 'DELIVERED';
}

/**
 * Vérifie si le QR code est toujours valide.
 */
public function isQrCodeValid(): bool
{
    // Le QR code est valide s'il existe et n'a pas expiré
    return $this->qr_token !== null 
           && $this->qr_expires_at !== null 
           && $this->qr_expires_at->isFuture()
           && $this->qr_status !== 'used';
}

/**
 * Génère un nouveau token de QR code avec une date d'expiration.
 */
public function generateQrToken(): void
{
    $this->qr_token = bin2hex(random_bytes(32)); // Token aléatoire sécurisé
    $this->qr_expires_at = now()->addDays(7); // Valide pendant 7 jours
    $this->qr_status = 'valid';
}
}