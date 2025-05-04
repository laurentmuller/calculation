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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CheckSubClassTraitTest extends TestCase
{
    use CheckSubClassTrait;

    /**
     * @phpstan-return \Generator<int, array{0: string|object, 1: class-string, 2?: true}>
     */
    public static function getSubClass(): \Generator
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
        yield ['ZZ', AbstractEntity::class, true];
    }

    /**
     * @phpstan-param class-string $target
     */
    #[DataProvider('getSubClass')]
    public function testSubClass(string|object $source, string $target, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $this->checkSubClass($source, $target);
        $this->expectNotToPerformAssertions();
    }
}
