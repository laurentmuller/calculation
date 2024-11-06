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

use App\Enums\TableView;
use App\Resolver\DataQueryValueResolver;
use App\Table\DataQuery;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataQueryValueResolverTest extends TestCase
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
        self::assertInstanceOf(DataQuery::class, $query);
        self::assertFalse($query->callback);
        self::assertSame(0, $query->id);
        self::assertSame(TableView::TABLE, $query->view);
        self::assertSame(0, $query->offset);
        self::assertSame(20, $query->limit);

        self::assertSame('', $query->search);
        self::assertSame('', $query->sort);
        self::assertSame('asc', $query->order);

        self::assertSame('', $query->prefix);

        self::assertSame(0, $query->groupId);
        self::assertSame(0, $query->categoryId);
        self::assertSame(0, $query->stateId);
        self::assertSame(0, $query->stateEditable);

        self::assertSame('', $query->level);
        self::assertSame('', $query->channel);
        self::assertSame('', $query->entity);
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
    public function testWithQuery(): void
    {
        $parameters = [
            'view' => TableView::CUSTOM->value,
            'offset' => 10,
            'limit' => 50,
        ];

        $resolver = $this->createResolver();
        $argument = $this->createArgumentMetadata();
        $request = $this->createRequest($parameters);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
        self::assertSame(TableView::CUSTOM, $query->view);
        self::assertSame(10, $query->offset);
        self::assertSame(50, $query->limit);
    }

    /**
     * @throws Exception
     */
    public function testWithValidatorError(): void
    {
        $error = 'error';
        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')
            ->willReturn(1);
        $violationList->method('__toString')
            ->willReturn($error);
        $resolver = $this->createResolver($violationList);
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata();

        self::expectException(HttpException::class);
        self::expectExceptionMessage($error);
        $resolver->resolve($request, $argument);
    }

    private function createAccessor(): PropertyAccessorInterface
    {
        return PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws Exception
     */
    private function createArgumentMetadata(string $type = DataQuery::class): MockObject&ArgumentMetadata
    {
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->expects(self::once())
            ->method('getType')
            ->willReturn($type);

        return $argument;
    }

    private function createRequest(array $parameters = []): Request
    {
        return Request::create('/', Request::METHOD_GET, $parameters);
    }

    /**
     * @throws Exception
     */
    private function createResolver(?ConstraintViolationListInterface $violationList = null): DataQueryValueResolver
    {
        $accessor = $this->createAccessor();
        $validator = $this->createMock(ValidatorInterface::class);
        if ($violationList instanceof ConstraintViolationListInterface) {
            $validator->method('validate')
                ->willReturn($violationList);
        }

        return new DataQueryValueResolver($accessor, $validator);
    }
}
