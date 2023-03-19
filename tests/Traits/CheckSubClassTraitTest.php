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
use App\Traits\CheckSubClassTrait;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(CheckSubClassTrait::class)]
class CheckSubClassTraitTest extends TestCase
{
    use CheckSubClassTrait;

    public static function getSubClass(): array
    {
        return [
            [Calculation::class, Calculation::class],
            [Calculation::class, AbstractEntity::class],

            [new Calculation(), Calculation::class],
            [new Calculation(), AbstractEntity::class],

            ['\App\Entity\Calculation', Calculation::class], // @phpstan-ignore-line
            ['\App\Entity\Calculation', AbstractEntity::class], // @phpstan-ignore-line

            ['ZZ', AbstractEntity::class, true],
        ];
    }

    /**
     * @psalm-param class-string $target
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getSubClass')]
    public function testSubClass(string|object $source, string $target, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $this->checkSubClass($source, $target);
        $this->expectNotToPerformAssertions();
    }
}
