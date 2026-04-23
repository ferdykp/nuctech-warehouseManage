<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Mechanical', 'description' => 'Mechanical parts and components'],
            ['name' => 'Electrical', 'description' => 'Electrical parts, cables, and components'],
            ['name' => 'Software', 'description' => 'Software components and licenses'],
            ['name' => 'Consumable', 'description' => 'Consumable parts'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
