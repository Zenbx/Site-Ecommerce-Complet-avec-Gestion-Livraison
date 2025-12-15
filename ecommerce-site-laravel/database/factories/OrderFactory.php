<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'client_id' => Client::factory(),
            'total_amount' => $this->faker->randomFloat(2, 50, 500),
            'delivery_fee' => 15.00,
            'delivery_address' => $this->faker->address,
            'notes' => $this->faker->optional()->sentence,
            'status' => 'PENDING',
            'payment_status' => 'PENDING',
        ];
    }
}
