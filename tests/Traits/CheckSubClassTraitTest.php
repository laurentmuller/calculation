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

namespace App\Tests\Traits;

use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Interfaces\EntityInterface;
use App\Traits\CheckSubClassTrait;
use App\Utils\StringUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckSubClassTraitTest extends TestCase
{
    use CheckSubClassTrait;

    public static function getInvalidSubClass(): \Generator
    {
        yield [new \stdClass(), AbstractEntity::class];
        yield ['ZZ', AbstractEntity::class];
    }

    public static function getValidSubClass(): \Generator
    {
        yield [Calculation::class, Calculation::class];
        yield [Calculation::class, AbstractEntity::class];
        yield [Calculation::class, EntityInterface::class];
        yield [new Calculation(), Calculation::class];
        yield [new Calculation(), AbstractEntity::class];
        yield [new Calculation(), EntityInterface::class];
        yield ['\App\Entity\Calculation', Calculation::class];
        yield ['\App\Entity\Calculation', AbstractEntity::class];
        yield ['\App\Entity\Calculation', EntityInterface::class];
    }

    /**
     * @param class-string $target
     */
    #[DataProvider('getInvalidSubClass')]
    public function testSubClassInvalid(string|object $source, string $target): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('%s expected, %s given.', $target, StringUtils::getDebugType($source)));
        $this->checkSubClass($source, $target);
    }

    /**
     * @param class-string $target
     */
    #[DataProvider('getValidSubClass')]
    public function testSubClassValid(string|object $source, string $target): void
    {
        self::expectNotToPerformAssertions();
        $this->checkSubClass($source, $target);
    }
}
