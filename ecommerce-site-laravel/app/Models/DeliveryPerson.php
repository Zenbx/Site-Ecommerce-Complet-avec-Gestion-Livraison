<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="DeliveryPerson",
 *     required={"name", "email", "password", "id_card_number", "address"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
 *     @OA\Property(property="id_card_number", type="string", example="AB123456"),
 *     @OA\Property(property="address", type="string", example="456 Delivery Rd"),
 *     @OA\Property(property="photo_url", type="string", example="http://example.com/photo.jpg"),
 *     @OA\Property(property="is_available", type="boolean", example=true),
 *     @OA\Property(property="current_latitude", type="number", format="float", example=3.8480),
 *     @OA\Property(property="current_longitude", type="number", format="float", example=11.5021),
 *     @OA\Property(property="is_online", type="boolean", example=true),
 *     @OA\Property(property="last_location_update", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class DeliveryPerson extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'delivery_persons';

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_card_number',
        'address',
        'photo_url',
        'is_available',
        'must_change_password',
        'current_latitude',
        'current_longitude',
        'is_online',
        'last_location_update',
        'current_address'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'must_change_password' => 'boolean',
        'is_online' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_location_update' => 'datetime',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
    ];

    // Relations
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    // Password mutator
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Update delivery person location
     */
    public function updateLocation(float $latitude, float $longitude, ?string $address = null): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'current_address' => $address,
            'last_location_update' => now(),
            'is_online' => true,
        ]);
    }

    /**
     * Check if delivery person is recently active (within last 2 minutes)
     */
    public function isRecentlyActive(): bool
    {
        if (!$this->last_location_update) {
            return false;
        }
        
        return $this->last_location_update->diffInMinutes(now()) <= 2;
    }

    /**
     * Get location as array
     */
    public function getLocationAttribute(): ?array
    {
        if (!$this->current_latitude || !$this->current_longitude) {
            return null;
        }

        return [
            'latitude' => (float) $this->current_latitude,
            'longitude' => (float) $this->current_longitude,
            'address' => $this->current_address,
            'updated_at' => $this->last_location_update?->toISOString(),
        ];
    }
}