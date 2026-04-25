<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Acil', 'color' => '#ff4d4f', 'description' => 'Hemen yapılması gerekenler'],
            ['name' => 'Rutin', 'color' => '#1890ff', 'description' => 'Günlük işler'],
            ['name' => 'Dolgu Malzemeleri', 'color' => '#52c41a', 'description' => 'Kompozit, amalgam vb.'],
            ['name' => 'Cerrahi', 'color' => '#722ed1', 'description' => 'Cerrahi alet ve malzemeler'],
            ['name' => 'İmplant', 'color' => '#faad14', 'description' => 'İmplant ve parçaları'],
            ['name' => 'Sarf Malzeme', 'color' => '#13c2c2', 'description' => 'Eldiven, maske vb.'],
        ];

        foreach ($categories as $category) {
            \App\Modules\Category\Models\Category::create([
                'name' => $category['name'],
                'color' => $category['color'],
                'description' => $category['description'],
                'is_active' => true,
                'company_id' => 1, // Denti Merkez Klinik
            ]);
        }
    }
}
