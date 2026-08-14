<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Infrastructure\Persistence\Models\Category;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sabzavotlar', 'slug' => 'sabzavotlar', 'image' => 'https://images.unsplash.com/photo-1566385101042-1a0aa0c1268c?auto=format&fit=crop&w=600&h=600&q=85'],
            ['name' => 'Mevalar', 'slug' => 'mevalar', 'image' => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=600&h=600&q=85'],
            ['name' => 'Don va dukkaklilar', 'slug' => 'don-va-dukkaklilar', 'image' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?auto=format&fit=crop&w=600&h=600&q=85'],
        ];

        foreach ($categories as $category) {
            Category::create([...$category, 'parent_id' => null, 'is_active' => true]);
        }
    }
}
