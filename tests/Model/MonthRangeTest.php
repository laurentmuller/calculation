<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\MonthRange;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MonthRangeTest extends TestCase
{
    use DateAssertTrait;

    public function testDatesWithSingleDate(): void
    {
        $date = new DatePoint('2024-06-06');
        $monthRange = new MonthRange()
            ->withFrom($date)
            ->withTo($date);
        self::assertDateTimeEquals('2024-06-01 00:00:00', $monthRange->from);
        self::assertDateTimeEquals('2024-06-30 23:59:59', $monthRange->to);
    }

    public function testDatesWithTwoDates(): void
    {
        $monthRange = new MonthRange()
            ->withFrom(new DatePoint('2024-06-06'))
            ->withTo(new DatePoint('2024-07-06'));
        self::assertDateTimeEquals('2024-06-01 00:00:00', $monthRange->from);
        self::assertDateTimeEquals('2024-07-31 23:59:59', $monthRange->to);
    }

    public function testMonthsWithSingleDate(): void
    {
        $date = new DatePoint('2024-06-06');
        $monthRange = new MonthRange()
            ->withFrom($date)
            ->withTo($date);
        $actual = $monthRange->months();
        self::assertSame(1, $actual);
    }

    public function testMonthsWithTwoDates(): void
    {
        $monthRange = new MonthRange()
            ->withFrom(new DatePoint('2024-06-06'))
            ->withTo(new DatePoint('2024-07-06'));
        $actual = $monthRange->months();
        self::assertSame(2, $actual);
    }

    public function testValidationFail(): void
    {
        $monthRange = new MonthRange()
            ->withFrom(new DatePoint('2024-06-06'))
            ->withTo(new DatePoint('2024-05-06'));
        $validator = $this->createValidator();
        $errors = $validator->validate($monthRange);
        self::assertCount(1, $errors);
        self::assertSame('month_range.end_date', $errors[0]?->getMessage());
    }

    public function testValidationValid(): void
    {
        $date = new DatePoint('2024-06-06');
        $monthRange = new MonthRange()
            ->withFrom($date)
            ->withTo($date);
        $validator = $this->createValidator();
        $errors = $validator->validate($monthRange);
        self::assertCount(0, $errors);
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
