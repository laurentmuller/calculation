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

use App\Model\TaskComputeQuery;
use App\Resolver\TaskComputeQueryValueResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[CoversClass(TaskComputeQueryValueResolver::class)]
class TaskComputeQueryValueResolverTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testDefault(): void
    {
        $request = Request::create('/');
        $argument = $this->createArgumentMetadata();
        $resolver = new TaskComputeQueryValueResolver();

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $query = $actual[0];
        self::assertInstanceOf(TaskComputeQuery::class, $query);
        self::assertSame(0, $query->id);
        self::assertSame(1.0, $query->quantity);
        self::assertSame([], $query->items);
    }

    /**
     * @throws Exception
     */
    public function testInvalidType(): void
    {
        $request = Request::create('/');
        $argument = $this->createArgumentMetadata(Request::class);
        $resolver = new TaskComputeQueryValueResolver();

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(0, $actual);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function testValid(): void
    {
        $values = [
            'id' => 1,
            'quantity' => 10.0,
            'items' => [1, 2, 3],
        ];
        $content = \json_encode($values, \JSON_THROW_ON_ERROR);
        $request = Request::create('/', method: Request::METHOD_POST, content: $content);
        $argument = $this->createArgumentMetadata();
        $resolver = new TaskComputeQueryValueResolver();

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $query = $actual[0];
        self::assertInstanceOf(TaskComputeQuery::class, $query);
        self::assertSame(1, $query->id);
        self::assertSame(10.0, $query->quantity);
        self::assertSame([1, 2, 3], $query->items);
    }

    /**
     * @throws Exception
     */
    private function createArgumentMetadata(string $type = TaskComputeQuery::class): MockObject&ArgumentMetadata
    {
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->expects(self::once())
            ->method('getType')
            ->willReturn($type);

        return $argument;
    }
}
