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
        'must_change_password'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'must_change_password' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}