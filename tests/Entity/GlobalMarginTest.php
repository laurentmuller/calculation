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

use App\Entity\GlobalMargin;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GlobalMargin::class)]
class GlobalMarginTest extends EntityValidatorTestCase
{
    public function testAllLessZero(): void
    {
        $margin = $this->getGlobalMargin(-2, -1, -1);
        $results = $this->validate($margin, 3);
        $this->validatePaths($results, 'margin', 'maximum', 'minimum');
    }

    public function testContains(): void
    {
        $margin = $this->getGlobalMargin(0.0, 10.0);
        self::assertFalse($margin->contains(-0.01));
        self::assertTrue($margin->contains(0.0));
        self::assertTrue($margin->contains(9.99));
        self::assertFalse($margin->contains(10.0));
    }

    public function testDefaultValues(): void
    {
        $margin = new GlobalMargin();
        self::assertSame(1.0, $margin->getMargin());
        self::assertSame(0.0, $margin->getMinimum());
        self::assertSame(0.0, $margin->getMaximum());
    }

    public function testMarginAmount(): void
    {
        $margin = $this->getGlobalMargin(0.0, 10.0, 2.5);
        self::assertSame(2.5, $margin->getMarginAmount(1.0));
        self::assertSame(5.0, $margin->getMarginAmount(2.0));
        self::assertSame(25.0, $margin->getMarginAmount(10.0));
    }

    public function testMarginLessOne(): void
    {
        $margin = $this->getGlobalMargin(0, 1, 0.9);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'margin');
    }

    public function testMaxLessMin(): void
    {
        $margin = $this->getGlobalMargin(10, 9);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'maximum');
    }

    public function testMinLessZero(): void
    {
        $margin = $this->getGlobalMargin(-1, 1);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'minimum');
    }

    public function testValid(): void
    {
        $margin = $this->getGlobalMargin(0, 10);
        $this->validate($margin);
    }

    private function getGlobalMargin(float $minimum, float $maximum, float $margin = 1.0): GlobalMargin
    {
        $entity = new GlobalMargin();
        $entity->setMinimum($minimum)
            ->setMaximum($maximum)
            ->setMargin($margin);

        return $entity;
    }
}
