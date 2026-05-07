<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    protected $model = \App\Models\Stock::class;

    public function definition(): array
    {
        $hasSubUnit = $this->faker->boolean(30);
        $subUnitMultiplier = $hasSubUnit ? $this->faker->randomElement([10, 12, 20, 100]) : null;
        $currentStock = $this->faker->numberBetween(0, 500);
        $currentSubStock = $hasSubUnit ? $this->faker->numberBetween(0, $subUnitMultiplier - 1) : 0;

        return [
            'product_id' => \App\Models\Product::factory(),
            'clinic_id' => \App\Models\Clinic::factory(),
            'supplier_id' => \App\Models\Supplier::factory()->nullable(),
            'batch_code' => 'BATCH-'.$this->faker->randomNumber(6),
            'current_stock' => $currentStock,
            'current_sub_stock' => $currentSubStock,
            'has_sub_unit' => $hasSubUnit,
            'sub_unit_name' => $hasSubUnit ? $this->faker->randomElement(['tablet', 'ampul', 'flakon']) : null,
            'sub_unit_multiplier' => $subUnitMultiplier,
            'total_base_units' => $hasSubUnit ? ($currentStock * $subUnitMultiplier + $currentSubStock) : $currentStock,
            'purchase_price' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'TRY',
            'purchase_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween('now', '+2 years')
                : null,
            'expiry_yellow_days' => 30,
            'expiry_red_days' => 15,
            'storage_location' => $this->faker->randomElement(['A1', 'A2', 'B1', 'B2', 'Depo']),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->subDays($this->faker->numberBetween(1, 365)),
        ]);
    }

    public function nearExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->addDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->numberBetween(1, 5),
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 0,
            'current_sub_stock' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withSubUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_sub_unit' => true,
            'sub_unit_multiplier' => 10,
            'sub_unit_name' => 'tablet',
        ]);
    }
}
