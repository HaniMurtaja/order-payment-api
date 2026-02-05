<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition()
    {
        return [
            'payment_id' => strtoupper($this->faker->randomElement(['CC_', 'PP_']) . $this->faker->unique()->uuid()),
            'order_id' => Order::factory(),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'gateway_response' => json_encode(['success' => true]),
        ];
    }
}
