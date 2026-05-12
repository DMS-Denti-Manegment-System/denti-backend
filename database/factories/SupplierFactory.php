<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = \App\Models\Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Tedarik',
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'address' => $this->faker->address(),
            'tax_number' => $this->faker->numerify('### ### ## ##'),
            'website' => $this->faker->boolean(50) ? $this->faker->url() : null,
            'payment_terms' => $this->faker->randomElement(['Peşin', '15 gün', '30 gün', '60 gün']),
            'notes' => $this->faker->boolean(20) ? $this->faker->paragraph() : null,
            'is_active' => true,
        ];
    }

    public function nullable(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => null,
        ]);
    }
}
