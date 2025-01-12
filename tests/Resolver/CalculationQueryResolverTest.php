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

namespace App\Tests\Resolver;

use App\Model\CalculationQuery;
use App\Resolver\CalculationQueryResolver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CalculationQueryResolverTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testDefault(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata();

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(CalculationQuery::class, $query);
        self::assertFalse($query->adjust);
        self::assertSame(0.0, $query->userMargin);
        self::assertSame([], $query->groups);
    }

    /**
     * @throws Exception
     */
    public function testInvalidType(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata(Request::class);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(0, $actual);
    }

    /**
     * @throws Exception
     */
    public function testWithDefaultParams(): void
    {
        $parameters = [
            'adjust' => true,
            'userMargin' => 0.1,
            'groups' => [
                [
                    'id' => 10,
                    'total' => 50,
                ],
            ],
        ];

        $resolver = $this->createResolver();
        $argument = $this->createArgumentMetadata();
        $request = $this->createRequest($parameters);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(CalculationQuery::class, $query);
        self::assertTrue($query->adjust);
        self::assertSame(0.1, $query->userMargin);

        self::assertCount(1, $query->groups);
        $group = $query->groups[0];
        self::assertSame(10, $group->id);
        self::assertSame(50.0, $group->total);
    }

    /**
     * @throws Exception
     */
    public function testWithDefaultValue(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata();
        $argument->expects(self::once())
            ->method('hasDefaultValue')
            ->willReturn(true);
        $argument->expects(self::once())
            ->method('getDefaultValue')
            ->willReturn(new CalculationQuery());

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $query = $actual[0];
        self::assertInstanceOf(CalculationQuery::class, $query);
    }

    /**
     * @throws Exception
     */
    private function createArgumentMetadata(string $type = CalculationQuery::class): MockObject&ArgumentMetadata
    {
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->expects(self::once())
            ->method('getType')
            ->willReturn($type);

        return $argument;
    }

    private function createRequest(array $parameters = []): Request
    {
        return Request::create(
            uri: '/',
            method: Request::METHOD_POST,
            parameters: $parameters
        );
    }

    private function createResolver(): CalculationQueryResolver
    {
        return new CalculationQueryResolver();
    }
}
