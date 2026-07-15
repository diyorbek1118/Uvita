<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Auth;

use InvalidArgumentException;
use Modules\Auth\Domain\ValueObjects\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_valid_uzbek_number_is_accepted(): void
    {
        $phone = new PhoneNumber('+998901234567');

        $this->assertSame('+998901234567', $phone->value);
    }

    public function test_to_string_returns_value(): void
    {
        $phone = new PhoneNumber('+998971234567');

        $this->assertSame('+998971234567', (string) $phone);
    }

    public function test_equals_returns_true_for_same_number(): void
    {
        $a = new PhoneNumber('+998901234567');
        $b = new PhoneNumber('+998901234567');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_numbers(): void
    {
        $a = new PhoneNumber('+998901234567');
        $b = new PhoneNumber('+998901234568');

        $this->assertFalse($a->equals($b));
    }

    /** @dataProvider invalidPhones */
    public function test_invalid_phone_throws_exception(string $phone): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber($phone);
    }

    public static function invalidPhones(): array
    {
        return [
            'without plus'          => ['998901234567'],
            'wrong country code'    => ['+7901234567'],
            'too short'             => ['+99890123456'],
            'too long'              => ['+9989012345678'],
            'with letters'          => ['+998abc12345'],
            'empty string'          => [''],
            'spaces'                => ['+998 90 123 45 67'],
            'unknown prefix 44'     => ['+998441234567'],
            'unknown prefix 00'     => ['+998001234567'],
            'unknown prefix 12'     => ['+998121234567'],
            'unknown prefix 92'     => ['+998921234567'],
            'unknown prefix 96'     => ['+998961234567'],
            'landline prefix 71'    => ['+998711234567'],
        ];
    }

    public function test_all_valid_operator_prefixes(): void
    {
        $prefixes = ['33', '50', '77', '88', '90', '91', '93', '94', '95', '97', '98', '99'];

        foreach ($prefixes as $prefix) {
            $phone = new PhoneNumber("+998{$prefix}1234567");
            $this->assertSame("+998{$prefix}1234567", $phone->value);
        }
    }
}
