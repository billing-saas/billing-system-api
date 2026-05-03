<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity  = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 500);

        return [
            'description' => fake()->sentence(3),
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'total'       => $quantity * $unitPrice,
        ];
    }
}
