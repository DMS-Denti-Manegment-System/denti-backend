<?php

use App\Models\Category;
use App\Models\Company;

$defaultCategories = [
    ['name' => 'Sarf malzemeler', 'color' => '#1890ff', 'description' => 'Genel sarf malzemeleri'],
    ['name' => 'Dolgu malzemeleri', 'color' => '#52c41a', 'description' => 'Kompozit, amalgam vb.'],
    ['name' => 'Endodontik malzemeler', 'color' => '#faad14', 'description' => 'Kanal tedavisi malzemeleri'],
    ['name' => 'Cerrahi malzemeler', 'color' => '#ff4d4f', 'description' => 'Cerrahi alet ve sarflar'],
    ['name' => 'Protez malzemeleri', 'color' => '#722ed1', 'description' => 'Ölçü maddeleri, porselen vb.'],
    ['name' => 'Ortodontik malzemeler', 'color' => '#eb2f96', 'description' => 'Braket, tel vb.'],
    ['name' => 'Periodontolojik malzemeler', 'color' => '#13c2c2', 'description' => 'Diş eti tedavisi malzemeleri'],
    ['name' => 'Pedodontik malzemeler', 'color' => '#2f54eb', 'description' => 'Çocuk diş hekimliği malzemeleri'],
];

$companies = Company::all();

foreach ($companies as $company) {
    foreach ($defaultCategories as $catData) {
        Category::updateOrCreate(
            ['name' => $catData['name'], 'company_id' => $company->id],
            [
                'color' => $catData['color'],
                'description' => $catData['description'],
                'is_active' => true,
            ]
        );
    }
}

echo "Default categories have been created/updated for " . $companies->count() . " companies.\n";
