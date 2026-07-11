<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Admin\Infrastructure\Persistence\Models\Staff;
use Modules\Category\Infrastructure\Persistence\Models\Category;
use Modules\Product\Infrastructure\Persistence\Models\Product;

/**
 * Mahsulotlar. Aralash holat:
 *  - Admin yaratgan (manager_id = null)      → active
 *  - Manager yaratgan tasdiqlangan            → active
 *  - Manager yaratgan moderatsiyada           → inactive
 *  - Manager yaratgan rad etilgan             → rejected
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->all();
        $managerId   = Staff::where('role', 'manager')->value('id');

        $i = 0;

        // 22 ta admin yaratgan active mahsulot
        for ($n = 0; $n < 22; $n++) {
            $this->makeProduct(++$i, $categoryIds, null, 'active');
        }

        // 4 ta manager yaratgan, tasdiqlangan (active)
        for ($n = 0; $n < 4; $n++) {
            $this->makeProduct(++$i, $categoryIds, $managerId, 'active');
        }

        // 3 ta manager yaratgan, moderatsiyada (inactive)
        for ($n = 0; $n < 3; $n++) {
            $this->makeProduct(++$i, $categoryIds, $managerId, 'inactive');
        }

        // 1 ta manager yaratgan, rad etilgan (rejected)
        $this->makeProduct(++$i, $categoryIds, $managerId, 'rejected', 'Rasm sifati past');
    }

    private function makeProduct(int $i, array $categoryIds, ?int $managerId, string $status, ?string $rejectionReason = null): void
    {
        $name = ucfirst(fake()->words(2, true));

        Product::create([
            'name'             => $name,
            'slug'             => Str::slug($name . '-' . $i),
            'description'      => fake()->paragraph(3),
            'price'            => fake()->numberBetween(15000, 350000),
            'stock'            => fake()->numberBetween(0, 100),
            'status'           => $status,
            'images'           => [
                'https://picsum.photos/seed/uvita' . $i . '/400/300',
                'https://picsum.photos/seed/uvita' . $i . 'b/400/300',
            ],
            'category_id'      => fake()->randomElement($categoryIds),
            'manager_id'       => $managerId,
            'rejection_reason' => $rejectionReason,
        ]);
    }
}
