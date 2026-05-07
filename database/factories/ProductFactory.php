<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $products = [
            ['name' => 'Parol 500mg', 'sku' => 'PAR-500', 'unit' => 'tablet', 'category' => 'İlaç'],
            ['name' => 'Aspirin 100mg', 'sku' => 'ASP-100', 'unit' => 'tablet', 'category' => 'İlaç'],
            ['name' => 'İbuprofen 400mg', 'sku' => 'IBU-400', 'unit' => 'tablet', 'category' => 'İlaç'],
            ['name' => 'Amoklavin 625mg', 'sku' => 'AMO-625', 'unit' => 'tablet', 'category' => 'Antibiyotik'],
            ['name' => 'Dental Floss', 'sku' => 'DEN-FLO', 'unit' => 'adet', 'category' => 'Sarf Malzeme'],
            ['name' => 'Diş Fırçası', 'sku' => 'DIS-FIR', 'unit' => 'adet', 'category' => 'Sarf Malzeme'],
            ['name' => 'Lokal Anestezik', 'sku' => 'LOK-ANE', 'unit' => 'ampul', 'category' => 'Anestezi'],
            ['name' => 'Gazlı Bez', 'sku' => 'GAZ-BEZ', 'unit' => 'paket', 'category' => 'Sarf Malzeme'],
            ['name' => 'Eldiven (Latex)', 'sku' => 'ELD-LAT', 'unit' => 'kutu', 'category' => 'Koruyucu'],
            ['name' => 'Maske Cerrahi', 'sku' => 'MAS-CER', 'unit' => 'kutu', 'category' => 'Koruyucu'],
        ];

        $product = $this->faker->randomElement($products);

        return [
            'company_id' => \App\Models\Company::factory(),
            'clinic_id' => null, // Optional
            'name' => $product['name'],
            'sku' => $product['sku'].'-'.$this->faker->randomNumber(3),
            'description' => $this->faker->sentence(),
            'unit' => $product['unit'],
            'category' => $product['category'],
            'brand' => $this->faker->randomElement(['Bayer', 'Pfizer', 'Abbott', 'Sanofi', null]),
            'min_stock_level' => $this->faker->numberBetween(10, 50),
            'critical_stock_level' => $this->faker->numberBetween(5, 20),
            'yellow_alert_level' => $this->faker->numberBetween(15, 40),
            'red_alert_level' => $this->faker->numberBetween(5, 15),
            'has_expiration_date' => $this->faker->boolean(70),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withClinic(): static
    {
        return $this->state(fn (array $attributes) => [
            'clinic_id' => \App\Models\Clinic::factory(),
        ]);
    }
}
