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

namespace App\Tests\Parameter;

use App\Attribute\Parameter;
use App\Parameter\ParameterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @template T of ParameterInterface
 */
abstract class ParameterTestCase extends TestCase
{
    /**
     * @psalm-var T ParameterInterface
     */
    protected ParameterInterface $parameter;

    #[\Override]
    protected function setUp(): void
    {
        $this->parameter = $this->createParameter();
    }

    /**
     * @psalm-return \Generator<int, array{string, string}>
     */
    abstract public static function getParameterNames(): \Generator;

    /**
     * @psalm-return \Generator<int, array{string, mixed}>
     */
    abstract public static function getParameterValues(): \Generator;

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('getParameterNames')]
    public function testParameterName(string $name, string $expected): void
    {
        $attribute = $this->getAttribute($name);
        self::assertSame($expected, $attribute->name);
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('getParameterValues')]
    public function testParameterValue(string $name, mixed $expected): void
    {
        $attribute = $this->getAttribute($name);
        self::assertSame($expected, $attribute->default);
    }

    /**
     * @psalm-return  T
     */
    abstract protected function createParameter(): ParameterInterface;

    /**
     * @throws \ReflectionException
     */
    protected function getAttribute(string $name): Parameter
    {
        $attribute = Parameter::getAttributInstance($this->parameter, $name);
        self::assertNotNull($attribute);

        return $attribute;
    }
}
