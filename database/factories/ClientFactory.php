<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => fake()->uuid(),
            'name'         => fake()->name(),
            'email'        => fake()->unique()->safeEmail(),
            'phone'        => fake()->phoneNumber(),
            'company_name' => fake()->company(),
            'tax_number'   => fake()->numerify('TAX-########'),
            'address'      => fake()->streetAddress(),
            'city'         => fake()->city(),
            'postal_code'  => fake()->postcode(),
            'country'      => fake()->country(),
            'notes'        => fake()->optional()->sentence(),
        ];
    }
}
