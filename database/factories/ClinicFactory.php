<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clinic>
 */
class ClinicFactory extends Factory
{
    protected $model = \App\Models\Clinic::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Ana Klinik', 'Şube 1', 'Şube 2', 'Merkez Şube']),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'is_active' => true,
        ];
    }
}
