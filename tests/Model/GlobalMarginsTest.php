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

use App\Entity\GlobalMargin;
use App\Model\GlobalMargins;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GlobalMargins::class)]
class GlobalMarginsTest extends TestCase
{
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    public function testAddMargin(): void
    {
        $margins = new GlobalMargins();
        self::assertCount(0, $margins);

        $margin = $this->createMargin();
        $margins->addMargin($margin);
        self::assertCount(1, $margins);
        $margins->addMargin($margin);
        self::assertCount(1, $margins);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstruct(): void
    {
        $margins = new GlobalMargins();
        self::assertCount(0, $margins);

        $margins = new GlobalMargins([$this->createMargin()]);
        self::assertCount(1, $margins);
    }

    public function testCount(): void
    {
        $margins = new GlobalMargins();
        self::assertCount(0, $margins);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMargins(): void
    {
        $margins = new GlobalMargins();
        $actual = $margins->getMargins();
        self::assertCount(0, $actual);
        $margins->addMargin($this->createMargin());
        $actual = $margins->getMargins();
        self::assertCount(1, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRemoveMargin(): void
    {
        $margins = new GlobalMargins();
        $margin = $this->createMargin();
        self::assertCount(0, $margins);
        $margins->removeMargin($margin);
        self::assertCount(0, $margins);
        $margins->addMargin($margin);
        self::assertCount(1, $margins);
        $margins->removeMargin($margin);
        self::assertCount(0, $margins);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetMargins(): void
    {
        $margins = new GlobalMargins();
        self::assertCount(0, $margins);
        $margins->setMargins([$this->createMargin()]);
        self::assertCount(1, $margins);
        $margins->setMargins([]);
        self::assertCount(0, $margins);
    }

    /**
     * @throws \ReflectionException
     */
    private function createMargin(): GlobalMargin
    {
        $margin = new GlobalMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setMargin(1.0);

        return self::setId($margin);
    }
}
