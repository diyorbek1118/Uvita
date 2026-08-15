<?php

declare(strict_types=1);

namespace App\Shared\Services\Fee;

/**
 * Buyurtma narx breakdown'ini hisoblaydi (sof biznes qoidasi, tashqi bog'liqliksiz).
 *
 * Mijoz seller kiritgan narxni to'laydi. Platforma, kuryer, soliq va to'lov
 * xarajatlari shu summaning ichidan ushlanadi.
 */
final class OrderFeeCalculator
{
    public const PLATFORM_FEE_RATE = 0.10;
    public const COURIER_FEE_RATE = 0.05;
    public const TAX_FEE_RATE = 0.01;
    public const PAYMENT_FEE_RATE = 0.03;

    public function calculate(int $goodsTotal): OrderFinancials
    {
        $platformGross = (int) round($goodsTotal * self::PLATFORM_FEE_RATE);
        $courierFee    = $this->courierFee($goodsTotal);
        $taxFee        = (int) round($goodsTotal * self::TAX_FEE_RATE);
        $paymentFee    = (int) round($goodsTotal * self::PAYMENT_FEE_RATE);
        $totalDeductions = $platformGross + $courierFee + $taxFee + $paymentFee;

        return new OrderFinancials(
            sellerAmount:     $goodsTotal - $totalDeductions,
            platformFeeGross: $platformGross,
            courierFee:       $courierFee,
            taxFee:           $taxFee,
            paymentFee:       $paymentFee,
            platformFeeNet:   $platformGross + $taxFee + $paymentFee,
            customerTotal:    $goodsTotal,
        );
    }

    public function courierFee(int $goodsTotal): int
    {
        return (int) round($goodsTotal * self::COURIER_FEE_RATE);
    }

    /**
     * Kuryer haqi pog'onasining SQL CASE ifodasi (analytics agregatsiyasi uchun).
     * Tariflar shu yerda — yagona manba.
     */
    public static function courierFeeSql(string $column = 'total_price'): string
    {
        return "ROUND({$column} * ".self::COURIER_FEE_RATE.')';
    }
}
