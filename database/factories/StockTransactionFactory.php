<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $stock = Stock::factory();

        return [
            'stock_id' => $stock,
            'clinic_id' => \App\Models\Clinic::factory(),
            'company_id' => fn (array $attributes) => \App\Models\Stock::find($attributes['stock_id'])->company_id,
            'transaction_number' => 'TRX-'.$this->faker->unique()->numberBetween(1000, 99999),
            'quantity' => $quantity,
            'previous_stock' => 0,
            'new_stock' => $quantity,
            'type' => $this->faker->randomElement(['in', 'out', 'adjustment', 'transfer_in', 'transfer_out']),
            'performed_by' => 'Test User',
            'transaction_date' => now(),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
        ];
    }

    public function in(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'quantity' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function out(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'quantity' => $this->faker->numberBetween(1, 50),
        ]);
    }
}
