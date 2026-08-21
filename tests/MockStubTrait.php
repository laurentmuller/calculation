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

namespace App\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Trait to create a stub or a mock object.
 *
 * @phpstan-require-extends TestCase
 */
trait MockStubTrait
{
    /**
     * @template RealInstanceType of object
     *
     * @param class-string<RealInstanceType> $type
     *
     * @phpstan-return ($mock is true ? MockObject&RealInstanceType : Stub&RealInstanceType)
     */
    protected function createMockOrStub(string $type, bool $mock = false): object
    {
        return $mock ? self::createMock($type) : self::createStub($type);
    }
}
