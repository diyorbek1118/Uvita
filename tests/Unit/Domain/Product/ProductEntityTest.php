<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product;

use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Enums\ProductStatusEnum;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    private function makeProduct(
        ?int             $managerId = null,
        ProductStatusEnum $status   = ProductStatusEnum::Active,
    ): Product {
        return new Product(
            id:              1,
            name:            'Test mahsulot',
            slug:            'test-mahsulot',
            description:     'Tavsif',
            price:           50000,
            stock:           10,
            status:          $status,
            images:          ['img1.jpg'],
            categoryId:      1,
            managerId:       $managerId,
            rejectionReason: null,
            createdAt:       null,
            updatedAt:       null,
        );
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_create_by_admin_sets_active_status(): void
    {
        $product = Product::create(
            name:        'Mahsulot',
            slug:        'mahsulot',
            description: 'Tavsif',
            price:       30000,
            stock:       5,
            images:      [],
            categoryId:  1,
            managerId:   null,
        );

        $this->assertSame(ProductStatusEnum::Active, $product->status);
        $this->assertNull($product->managerId);
    }

    public function test_create_by_manager_sets_inactive_status(): void
    {
        $product = Product::create(
            name:        'Manager mahsuloti',
            slug:        'manager-mahsulot',
            description: 'Tavsif',
            price:       20000,
            stock:       3,
            images:      [],
            categoryId:  1,
            managerId:   42,
        );

        $this->assertSame(ProductStatusEnum::Inactive, $product->status);
        $this->assertSame(42, $product->managerId);
    }

    public function test_create_id_is_null(): void
    {
        $product = Product::create('A', 'a', 'b', 1, 1, [], 1);

        $this->assertNull($product->id);
    }

    public function test_create_rejection_reason_is_null(): void
    {
        $product = Product::create('A', 'a', 'b', 1, 1, [], 1);

        $this->assertNull($product->rejectionReason);
    }

    // ─── approve ──────────────────────────────────────────────────────────────

    public function test_approve_sets_active_status(): void
    {
        $product = $this->makeProduct(managerId: 5, status: ProductStatusEnum::Inactive);

        $approved = $product->approve();

        $this->assertSame(ProductStatusEnum::Active, $approved->status);
    }

    public function test_approve_clears_rejection_reason(): void
    {
        $product = new Product(
            id:              1,
            name:            'Test',
            slug:            'test',
            description:     'Desc',
            price:           1000,
            stock:           1,
            status:          ProductStatusEnum::Rejected,
            images:          [],
            categoryId:      1,
            managerId:       5,
            rejectionReason: 'Rasm sifatsiz',
            createdAt:       null,
            updatedAt:       null,
        );

        $approved = $product->approve();

        $this->assertNull($approved->rejectionReason);
    }

    public function test_approve_returns_new_instance(): void
    {
        $product  = $this->makeProduct(status: ProductStatusEnum::Inactive);
        $approved = $product->approve();

        $this->assertNotSame($product, $approved);
    }

    public function test_approve_preserves_other_fields(): void
    {
        $product  = $this->makeProduct(managerId: 7, status: ProductStatusEnum::Inactive);
        $approved = $product->approve();

        $this->assertSame($product->name, $approved->name);
        $this->assertSame($product->price, $approved->price);
        $this->assertSame($product->managerId, $approved->managerId);
    }

    // ─── reject ───────────────────────────────────────────────────────────────

    public function test_reject_sets_rejected_status(): void
    {
        $product  = $this->makeProduct(status: ProductStatusEnum::Inactive);
        $rejected = $product->reject('Noto\'g\'ri kategoriya');

        $this->assertSame(ProductStatusEnum::Rejected, $rejected->status);
    }

    public function test_reject_sets_rejection_reason(): void
    {
        $product  = $this->makeProduct(status: ProductStatusEnum::Inactive);
        $rejected = $product->reject('Rasm sifatsiz');

        $this->assertSame('Rasm sifatsiz', $rejected->rejectionReason);
    }

    public function test_reject_returns_new_instance(): void
    {
        $product  = $this->makeProduct(status: ProductStatusEnum::Inactive);
        $rejected = $product->reject('Sabab');

        $this->assertNotSame($product, $rejected);
    }

    // ─── modify ───────────────────────────────────────────────────────────────

    public function test_modify_updates_fields(): void
    {
        $product  = $this->makeProduct();
        $modified = $product->modify(
            name:        'Yangi nom',
            slug:        'yangi-nom',
            description: 'Yangi tavsif',
            price:       75000,
            stock:       20,
            images:      ['new.jpg'],
            categoryId:  2,
        );

        $this->assertSame('Yangi nom', $modified->name);
        $this->assertSame(75000, $modified->price);
        $this->assertSame(20, $modified->stock);
    }

    public function test_modify_preserves_status_and_manager(): void
    {
        $product  = $this->makeProduct(managerId: 5, status: ProductStatusEnum::Inactive);
        $modified = $product->modify('A', 'a', 'b', 1, 1, [], 1);

        $this->assertSame(ProductStatusEnum::Inactive, $modified->status);
        $this->assertSame(5, $modified->managerId);
    }
}
