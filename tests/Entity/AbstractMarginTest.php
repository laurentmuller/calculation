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

namespace App\Tests\Entity;

use App\Entity\AbstractMargin;
use PHPUnit\Framework\TestCase;

final class AbstractMarginTest extends TestCase
{
    public function testContains(): void
    {
        $margin = $this->getEntity(0.0, 10.0);
        self::assertFalse($margin->contains(-0.01));
        self::assertTrue($margin->contains(0.0));
        self::assertTrue($margin->contains(9.99));
        self::assertFalse($margin->contains(10.0));
    }

    public function testDefaultValues(): void
    {
        $margin = $this->getEntity();
        self::assertSame(1.0, $margin->margin);
        self::assertSame(0.0, $margin->minimum);
        self::assertSame(0.0, $margin->maximum);
    }

    public function testDelta(): void
    {
        $margin = $this->getEntity(1.0, 10.0);
        self::assertSame(9.0, $margin->getDelta());
    }

    public function testDisplay(): void
    {
        $margin = $this->getEntity();
        $actual = $margin->getDisplay();
        self::assertSame('0.00 - 0.00', $actual);

        $margin->withMinimum(100)
            ->withMaximum(200);
        $actual = $margin->getDisplay();
        self::assertSame('100.00 - 200.00', $actual);

        $margin->withMinimum(1000)
            ->withMaximum(2000);
        $actual = $margin->getDisplay();
        self::assertSame("1'000.00 - 2'000.00", $actual);
    }

    public function testMarginAmount(): void
    {
        $margin = $this->getEntity(0.0, 10.0, 2.5);
        self::assertSame(2.5, $margin->getMarginAmount(1.0));
        self::assertSame(5.0, $margin->getMarginAmount(2.0));
        self::assertSame(25.0, $margin->getMarginAmount(10.0));
    }

    private function getEntity(float $minimum = 0.0, float $maximum = 0.0, float $margin = 1.0): AbstractMargin
    {
        $entity = new class extends AbstractMargin {
            public float $maximum = 0.0 {
                get {
                    return $this->maximum;
                }
            }
            public float $minimum = 0.0 {
                get {
                    return $this->minimum;
                }
            }
        };

        return $entity->withMinimum($minimum)
            ->withMaximum($maximum)
            ->withMargin($margin);
    }
}
