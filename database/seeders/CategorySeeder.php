<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Infrastructure\Persistence\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::insert([
            ['name' => 'Pizza',       'slug' => 'pizza',       'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Burgerlar',   'slug' => 'burgers',     'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Sushi',       'slug' => 'sushi',       'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Pasta',       'slug' => 'pasta',       'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Desertlar',   'slug' => 'desert',      'image' => null, 'parent_id' => null, 'is_active' => true],
            ['name' => 'Ichimliklar', 'slug' => 'ichimliklar', 'image' => null, 'parent_id' => null, 'is_active' => true],
        ]);
    }
}
