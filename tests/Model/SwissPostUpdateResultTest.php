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

use App\Model\SwissPostUpdateResult;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class SwissPostUpdateResultTest extends TestCase
{
    use DateAssertTrait;

    public function testAdd(): void
    {
        $result = new SwissPostUpdateResult();
        self::assertSame(0, $result->getValidEntriesCount());
        self::assertSame(0, $result->getInvalidEntriesCount());

        $result->addCity(true);
        $result->addState(true);
        $result->addStreet(true);
        self::assertSame(3, $result->getValidEntriesCount());
        self::assertSame(0, $result->getInvalidEntriesCount());

        $result->addCity(false);
        $result->addState(false);
        $result->addStreet(false);
        self::assertSame(3, $result->getValidEntriesCount());
        self::assertSame(3, $result->getInvalidEntriesCount());
    }

    public function testEmpty(): void
    {
        $actual = new SwissPostUpdateResult();
        self::assertNull($actual->getError());

        self::assertCount(3, $actual->getInvalidEntries());
        self::assertSame(0, $actual->getInvalidEntriesCount());

        self::assertCount(3, $actual->getOldEntries());
        self::assertSame(0, $actual->getOldEntriesCount());

        self::assertSame('', $actual->getSourceFile());
        self::assertSame('', $actual->getSourceName());

        self::assertCount(3, $actual->getValidEntries());
        self::assertSame(0, $actual->getValidEntriesCount());

        self::assertNull($actual->getValidity());
        self::assertFalse($actual->isOverwrite());
        self::assertTrue($actual->isValid());
    }

    public function testError(): void
    {
        $expected = 'This is an error';
        $result = new SwissPostUpdateResult();
        $result->setError($expected);
        self::assertSame($expected, $result->getError());
        self::assertFalse($result->isValid());
    }

    public function testOldEntries(): void
    {
        $expected = [
            'state' => 1,
            'city' => 10,
            'street' => 100,
        ];
        $result = new SwissPostUpdateResult();
        $result->setOldEntries($expected);
        self::assertSame($expected, $result->getOldEntries());
        self::assertSame(111, $result->getOldEntriesCount());
    }

    public function testProperties(): void
    {
        $result = new SwissPostUpdateResult();

        self::assertFalse($result->isOverwrite());
        $result->setOverwrite(true);
        self::assertTrue($result->isOverwrite());

        $expected = 'sourceFile';
        $result->setSourceFile($expected);
        self::assertSame($expected, $result->getSourceFile());

        $expected = 'sourceName';
        $result->setSourceName($expected);
        self::assertSame($expected, $result->getSourceName());
    }

    public function testValidity(): void
    {
        $expected = new DatePoint();
        $result = new SwissPostUpdateResult();
        $result->setValidity($expected);
        self::assertTimestampEquals($expected, $result->getValidity());
    }
}
