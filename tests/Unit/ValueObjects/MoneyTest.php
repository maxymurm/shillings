<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function it_creates_from_decimal(): void
    {
        $money = Money::fromDecimal('100.50', 'USD');

        $this->assertEquals(10050, $money->getNumerator());
        $this->assertEquals(100, $money->getDenominator());
        $this->assertEquals('USD', $money->getCurrencyCode());
        $this->assertEquals(100.50, $money->toDecimal());
    }

    #[Test]
    public function it_creates_from_fraction(): void
    {
        $money = Money::fromFraction(10050, 100, 'EUR');

        $this->assertEquals(10050, $money->getNumerator());
        $this->assertEquals(100, $money->getDenominator());
        $this->assertEquals('EUR', $money->getCurrencyCode());
    }

    #[Test]
    public function it_creates_from_cents(): void
    {
        $money = Money::fromCents(1050, 'USD');

        $this->assertEquals(1050, $money->getNumerator());
        $this->assertEquals(100, $money->getDenominator());
        $this->assertEquals(10.50, $money->toDecimal());
    }

    #[Test]
    public function it_creates_zero(): void
    {
        $money = Money::zero('USD');

        $this->assertTrue($money->isZero());
        $this->assertEquals(0, $money->toDecimal());
    }

    #[Test]
    public function it_adds_money(): void
    {
        $a = Money::fromDecimal('100.50', 'USD');
        $b = Money::fromDecimal('50.25', 'USD');

        $result = $a->add($b);

        $this->assertEquals(150.75, $result->toDecimal());
    }

    #[Test]
    public function it_adds_with_different_denominators(): void
    {
        $a = Money::fromFraction(100, 3); // 33.333...
        $b = Money::fromFraction(100, 7); // 14.285...

        $result = $a->add($b);

        // (100/3) + (100/7) = (700 + 300) / 21 = 1000/21
        $this->assertEquals(1000, $result->getNumerator());
        $this->assertEquals(21, $result->getDenominator());
    }

    #[Test]
    public function it_subtracts_money(): void
    {
        $a = Money::fromDecimal('100.50', 'USD');
        $b = Money::fromDecimal('50.25', 'USD');

        $result = $a->subtract($b);

        $this->assertEquals(50.25, $result->toDecimal());
    }

    #[Test]
    public function it_multiplies_by_integer(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $result = $money->multiply(3);

        $this->assertEquals(300.00, $result->toDecimal());
    }

    #[Test]
    public function it_multiplies_by_float(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $result = $money->multiply(1.5);

        $this->assertEquals(150.00, $result->toDecimal());
    }

    #[Test]
    public function it_divides_by_integer(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $result = $money->divide(4);

        $this->assertEquals(25.00, $result->toDecimal());
    }

    #[Test]
    public function it_divides_by_float(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $result = $money->divide(2.5);

        $this->assertEquals(40.00, $result->toDecimal());
    }

    #[Test]
    public function it_throws_on_divide_by_zero(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $this->expectException(InvalidArgumentException::class);
        $money->divide(0);
    }

    #[Test]
    public function it_negates(): void
    {
        $money = Money::fromDecimal('100.50', 'USD');

        $result = $money->negate();

        $this->assertEquals(-100.50, $result->toDecimal());
        $this->assertTrue($result->isNegative());
    }

    #[Test]
    public function it_gets_absolute_value(): void
    {
        $money = Money::fromDecimal('-100.50', 'USD');

        $result = $money->abs();

        $this->assertEquals(100.50, $result->toDecimal());
        $this->assertTrue($result->isPositive());
    }

    #[Test]
    public function it_checks_zero(): void
    {
        $zero = Money::zero('USD');
        $nonZero = Money::fromDecimal('0.01', 'USD');

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    #[Test]
    public function it_checks_positive_negative(): void
    {
        $positive = Money::fromDecimal('100.00', 'USD');
        $negative = Money::fromDecimal('-100.00', 'USD');
        $zero = Money::zero('USD');

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($positive->isNegative());

        $this->assertTrue($negative->isNegative());
        $this->assertFalse($negative->isPositive());

        $this->assertFalse($zero->isPositive());
        $this->assertFalse($zero->isNegative());
    }

    #[Test]
    public function it_compares_equality(): void
    {
        $a = Money::fromDecimal('100.50', 'USD');
        $b = Money::fromDecimal('100.50', 'USD');
        $c = Money::fromDecimal('100.51', 'USD');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function it_compares_equality_with_different_denominators(): void
    {
        $a = Money::fromFraction(100, 2); // 50
        $b = Money::fromFraction(200, 4); // 50

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_compares_greater_less(): void
    {
        $a = Money::fromDecimal('100.50', 'USD');
        $b = Money::fromDecimal('50.25', 'USD');

        $this->assertTrue($a->greaterThan($b));
        $this->assertTrue($a->greaterThanOrEqual($b));
        $this->assertFalse($a->lessThan($b));

        $this->assertTrue($b->lessThan($a));
        $this->assertTrue($b->lessThanOrEqual($a));
        $this->assertFalse($b->greaterThan($a));
    }

    #[Test]
    public function it_simplifies_fraction(): void
    {
        $money = Money::fromFraction(1000, 100);

        $simplified = $money->simplify();

        $this->assertEquals(10, $simplified->getNumerator());
        $this->assertEquals(1, $simplified->getDenominator());
    }

    #[Test]
    public function it_rounds_to_precision(): void
    {
        $money = Money::fromFraction(1234, 1000); // 1.234

        $rounded = $money->round(2);

        $this->assertEquals(1.23, $rounded->toDecimal());
    }

    #[Test]
    public function it_allocates_proportionally(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $allocated = $money->allocate([1, 1, 1]); // Split 3 ways

        $total = array_reduce($allocated, fn ($sum, $m) => $sum + $m->getNumerator(), 0);
        $this->assertEquals($money->getNumerator(), $total);

        // Should be 33.34, 33.33, 33.33 (remainder distributed)
        $this->assertEquals(3334, $allocated[0]->getNumerator());
        $this->assertEquals(3333, $allocated[1]->getNumerator());
        $this->assertEquals(3333, $allocated[2]->getNumerator());
    }

    #[Test]
    public function it_allocates_with_different_ratios(): void
    {
        $money = Money::fromDecimal('100.00', 'USD');

        $allocated = $money->allocate([50, 30, 20]); // 50%, 30%, 20%

        $this->assertEquals(50.00, $allocated[0]->toDecimal());
        $this->assertEquals(30.00, $allocated[1]->toDecimal());
        $this->assertEquals(20.00, $allocated[2]->toDecimal());
    }

    #[Test]
    public function it_converts_currency(): void
    {
        $usd = Money::fromDecimal('100.00', 'USD');
        $rate = Money::fromDecimal('0.85'); // 1 USD = 0.85 EUR

        $eur = $usd->convertTo('EUR', $rate);

        $this->assertEquals('EUR', $eur->getCurrencyCode());
        $this->assertEquals(85.00, $eur->toDecimal());
    }

    #[Test]
    public function it_throws_on_currency_mismatch(): void
    {
        $usd = Money::fromDecimal('100.00', 'USD');
        $eur = Money::fromDecimal('50.00', 'EUR');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $usd->add($eur);
    }

    #[Test]
    public function it_formats_with_separators(): void
    {
        $money = Money::fromDecimal('1234567.89', 'USD');

        $formatted = $money->format(2, '.', ',');

        $this->assertEquals('1,234,567.89', $formatted);
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $money = Money::fromDecimal('100.50', 'USD');

        $this->assertEquals('USD 100.50', (string) $money);
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $money = Money::fromDecimal('100.50', 'USD');

        $json = json_encode($money);
        $data = json_decode($json, true);

        $this->assertEquals(10050, $data['numerator']);
        $this->assertEquals(100, $data['denominator']);
        $this->assertEquals('USD', $data['currency_code']);
        $this->assertEquals(100.50, $data['decimal']);
    }

    #[Test]
    public function it_throws_on_invalid_denominator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromFraction(100, 0);
    }

    #[Test]
    public function it_throws_on_negative_denominator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromFraction(100, -1);
    }

    #[Test]
    public function it_handles_precision_with_many_decimals(): void
    {
        $money = Money::fromDecimal('123.456789', null, 6);

        $this->assertEquals(123456789, $money->getNumerator());
        $this->assertEquals(1000000, $money->getDenominator());
    }
}
