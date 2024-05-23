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

use App\Enums\Theme;
use App\Traits\ParameterTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(ParameterTrait::class)]
class ParameterTraitTest extends TestCase
{
    use ParameterTrait;

    public function testParameterBoolean(): void
    {
        $request = $this->createRequest();
        $actual = $this->getParamBoolean($request, 'key');
        self::assertFalse($actual);
    }

    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function testParameterEnum(): void
    {
        $request = $this->createRequest();
        $actual = $this->getParamEnum($request, 'key', Theme::DARK);
        self::assertSame(Theme::DARK, $actual);
    }

    public function testParameterFloat(): void
    {
        $request = $this->createRequest();
        $actual = $this->getParamFloat($request, 'key');
        self::assertSame(0.0, $actual);
    }

    public function testParameterInt(): void
    {
        $request = $this->createRequest();
        $actual = $this->getParamInt($request, 'key');
        self::assertSame(0, $actual);
    }

    public function testParameterString(): void
    {
        $request = $this->createRequest();
        $actual = $this->getParamString($request, 'key');
        self::assertSame('', $actual);
    }

    private function createRequest(): Request
    {
        return new Request();
    }
}
