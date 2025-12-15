<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'delivery_person_id' => null,
            'delivery_address' => $this->faker->address,
            'status' => 'PENDING',
            'tracking_code' => 'TRK-' . strtoupper($this->faker->bothify('??####')),
        ];
    }
}
