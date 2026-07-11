<?php

declare(strict_types=1);

namespace App\Shared\Services\Fee;

/**
 * Buyurtma narx breakdown'ini hisoblaydi (sof biznes qoidasi, tashqi bog'liqliksiz).
 *
 * Model:
 *   customer_total  = goods + 15% ustama
 *   platform_gross  = round(goods * 0.15)
 *   courier_fee     = pog'onali (goods bo'yicha):
 *                       goods < 200 000  -> 10 000
 *                       200 000..299 999 -> 15 000
 *                       >= 300 000       -> 20 000
 *   platform_net    = platform_gross - courier_fee
 *   seller_amount   = goods
 *
 * Misol (goods = 250 000): gross 37 500, courier 15 000, net 22 500,
 * seller 250 000, customer 287 500.
 */
final class OrderFeeCalculator
{
    public const PLATFORM_FEE_RATE = 0.15;

    public function calculate(int $goodsTotal): OrderFinancials
    {
        $platformGross = (int) round($goodsTotal * self::PLATFORM_FEE_RATE);
        $courierFee    = $this->courierFee($goodsTotal);

        return new OrderFinancials(
            sellerAmount:     $goodsTotal,
            platformFeeGross: $platformGross,
            courierFee:       $courierFee,
            platformFeeNet:   $platformGross - $courierFee,
            customerTotal:    $goodsTotal + $platformGross,
        );
    }

    public function courierFee(int $goodsTotal): int
    {
        return match (true) {
            $goodsTotal < 200_000 => 10_000,
            $goodsTotal < 300_000 => 15_000,
            default               => 20_000,
        };
    }

    /**
     * Kuryer haqi pog'onasining SQL CASE ifodasi (analytics agregatsiyasi uchun).
     * Tariflar shu yerda — yagona manba.
     */
    public static function courierFeeSql(string $column = 'total_price'): string
    {
        return "CASE WHEN {$column} < 200000 THEN 10000"
            . " WHEN {$column} < 300000 THEN 15000"
            . ' ELSE 20000 END';
    }
}
