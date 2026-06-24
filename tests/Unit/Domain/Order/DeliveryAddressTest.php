<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order;

use Modules\Order\Domain\ValueObjects\DeliveryAddress;
use PHPUnit\Framework\TestCase;

class DeliveryAddressTest extends TestCase
{
    private function makeAddress(array $overrides = []): DeliveryAddress
    {
        return new DeliveryAddress(
            region:   $overrides['region']   ?? 'Toshkent',
            district: $overrides['district'] ?? 'Yunusobod',
            street:   $overrides['street']   ?? 'Amir Temur',
            house:    $overrides['house']    ?? '1',
            landmark: $overrides['landmark'] ?? null,
        );
    }

    public function test_to_array_returns_all_fields(): void
    {
        $address = $this->makeAddress(['landmark' => 'Do\'kon yonida']);

        $array = $address->toArray();

        $this->assertSame('Toshkent', $array['region']);
        $this->assertSame('Yunusobod', $array['district']);
        $this->assertSame('Amir Temur', $array['street']);
        $this->assertSame('1', $array['house']);
        $this->assertSame("Do'kon yonida", $array['landmark']);
    }

    public function test_to_array_landmark_is_null_when_not_provided(): void
    {
        $array = $this->makeAddress()->toArray();

        $this->assertNull($array['landmark']);
    }

    public function test_from_array_creates_correct_address(): void
    {
        $data = [
            'region'   => 'Samarqand',
            'district' => 'Urgut',
            'street'   => 'Navoi',
            'house'    => '42',
            'landmark' => 'Maktab yonida',
        ];

        $address = DeliveryAddress::fromArray($data);

        $this->assertSame('Samarqand', $address->region);
        $this->assertSame('Urgut', $address->district);
        $this->assertSame('Navoi', $address->street);
        $this->assertSame('42', $address->house);
        $this->assertSame('Maktab yonida', $address->landmark);
    }

    public function test_from_array_landmark_defaults_to_null(): void
    {
        $address = DeliveryAddress::fromArray([
            'region'   => 'Toshkent',
            'district' => 'Chilonzor',
            'street'   => 'Bunyodkor',
            'house'    => '10',
        ]);

        $this->assertNull($address->landmark);
    }

    public function test_round_trip_from_array_to_array(): void
    {
        $original = [
            'region'   => 'Farg\'ona',
            'district' => 'Farg\'ona',
            'street'   => 'Mustaqillik',
            'house'    => '7A',
            'landmark' => null,
        ];

        $result = DeliveryAddress::fromArray($original)->toArray();

        $this->assertSame($original, $result);
    }
}
