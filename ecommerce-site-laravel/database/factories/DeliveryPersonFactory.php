<?php

namespace Database\Factories;

use App\Models\DeliveryPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryPersonFactory extends Factory
{
    protected $model = DeliveryPerson::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password', // Mutator will hash this
            'id_card_number' => strtoupper($this->faker->bothify('??######')),
            'address' => $this->faker->address,
            'photo_url' => $this->faker->imageUrl(),
            'is_available' => true,
        ];
    }
}
