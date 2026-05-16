<?php

namespace Database\Factories;

use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id'      => $this->faker->uuid(),
            'email'        => $this->faker->unique()->safeEmail(),
            'first_name'   => $this->faker->firstName(),
            'last_name'    => $this->faker->lastName(),
            'phone'        => $this->faker->phoneNumber(),
            'company_name' => $this->faker->company(),
            'tax_number'   => $this->faker->numerify('FR##########'),
            'address'      => $this->faker->streetAddress(),
            'city'         => $this->faker->city(),
            'postal_code'  => $this->faker->postcode(),
            'country'      => $this->faker->countryCode(),
            'currency'     => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'logo_path'    => null,
        ];
    }
}
