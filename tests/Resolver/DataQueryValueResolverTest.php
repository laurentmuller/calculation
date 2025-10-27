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
use App\Interfaces\TableInterface;
use App\Resolver\DataQueryValueResolver;
use App\Service\UrlGeneratorService;
use App\Table\AbstractCategoryItemTable;
use App\Table\CalculationTable;
use App\Table\CategoryTable;
use App\Table\DataQuery;
use App\Table\LogTable;
use App\Table\SearchTable;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DataQueryValueResolverTest extends TestCase
{
    use TranslatorMockTrait;

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

        self::assertSame(0, $query->getIntParameter(CategoryTable::PARAM_GROUP));
        self::assertSame(0, $query->getIntParameter(CalculationTable::PARAM_STATE));
        self::assertSame(0, $query->getIntParameter(CalculationTable::PARAM_EDITABLE));
        self::assertSame(0, $query->getIntParameter(AbstractCategoryItemTable::PARAM_CATEGORY));

        self::assertSame('', $query->getStringParameter(LogTable::PARAM_LEVEL));
        self::assertSame('', $query->getStringParameter(LogTable::PARAM_CHANNEL));
        self::assertSame('', $query->getStringParameter(SearchTable::PARAM_ENTITY));
    }

    public function testGetCookiePath(): void
    {
        $resolver = $this->createResolver();
        $class = new \ReflectionClass($resolver);
        $method = $class->getMethod('getCookiePath');
        $expected = '/';
        $actual = $method->invoke($resolver);
        self::assertSame($expected, $actual);
    }

    public function testInvalidKey(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest(['invalidKey' => 'value']);
        $argument = $this->createArgumentMetadata();
        self::expectException(BadRequestHttpException::class);
        $resolver->resolve($request, $argument);
    }

    public function testInvalidType(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata(Request::class);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertEmpty($actual);
    }

    public function testWithCaller(): void
    {
        $parameters = [
            UrlGeneratorService::PARAM_CALLER => '/',
        ];

        $resolver = $this->createResolver();
        $argument = $this->createArgumentMetadata();
        $request = $this->createRequest($parameters);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
    }

    public function testWithDefaultParams(): void
    {
        $parameters = [
            TableInterface::PARAM_ID => 1,
            TableInterface::PARAM_SEARCH => 'search',
            TableInterface::PARAM_SORT => 'sort',
            TableInterface::PARAM_ORDER => 'asc',
            TableInterface::PARAM_OFFSET => 10,
            TableInterface::PARAM_LIMIT => 50,
            TableInterface::PARAM_VIEW => TableView::TABLE->value,
        ];

        $resolver = $this->createResolver();
        $argument = $this->createArgumentMetadata();
        $request = $this->createRequest($parameters);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
        self::assertSame(1, $query->id);
        self::assertSame('search', $query->search);
        self::assertSame('sort', $query->sort);
        self::assertSame('asc', $query->order);
        self::assertSame(10, $query->offset);
        self::assertSame(50, $query->limit);
        self::assertSame(TableView::TABLE, $query->view);
    }

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
            ->willReturn(new DataQuery());

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
    }

    public function testWithNoDefaultValue(): void
    {
        $resolver = $this->createResolver();
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata();
        $argument->method('hasDefaultValue')
            ->willReturn(false);
        $argument->method('getDefaultValue')
            ->willReturn(null);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
    }

    public function testWithParameters(): void
    {
        $parameters = [
            CategoryTable::PARAM_GROUP => 1,
            CalculationTable::PARAM_STATE => 1,
            CalculationTable::PARAM_EDITABLE => 1,
            AbstractCategoryItemTable::PARAM_CATEGORY => 1,
            LogTable::PARAM_LEVEL => 'level',
            LogTable::PARAM_CHANNEL => 'channel',
            SearchTable::PARAM_ENTITY => 'entity',
        ];

        $resolver = $this->createResolver();
        $argument = $this->createArgumentMetadata();
        $request = $this->createRequest($parameters);

        $actual = $resolver->resolve($request, $argument);
        self::assertIsArray($actual);
        self::assertCount(1, $actual);

        $query = $actual[0];
        self::assertInstanceOf(DataQuery::class, $query);
        self::assertSame(1, $query->getIntParameter(CategoryTable::PARAM_GROUP));
        self::assertSame(1, $query->getIntParameter(CalculationTable::PARAM_STATE));
        self::assertSame(1, $query->getIntParameter(CalculationTable::PARAM_EDITABLE));
        self::assertSame(1, $query->getIntParameter(AbstractCategoryItemTable::PARAM_CATEGORY));
        self::assertSame('level', $query->getStringParameter(LogTable::PARAM_LEVEL));
        self::assertSame('channel', $query->getStringParameter(LogTable::PARAM_CHANNEL));
        self::assertSame('entity', $query->getStringParameter(SearchTable::PARAM_ENTITY));
    }

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

    public function testWithValidationError(): void
    {
        $violation = $this->createConstraintViolation();
        $violationList = new ConstraintViolationList([$violation]);
        $resolver = $this->createResolver($violationList);
        $request = $this->createRequest();
        $argument = $this->createArgumentMetadata();

        self::expectException(BadRequestHttpException::class);
        $resolver->resolve($request, $argument);
    }

    private function createArgumentMetadata(string $type = DataQuery::class): MockObject&ArgumentMetadata
    {
        $argument = $this->createMock(ArgumentMetadata::class);
        $argument->expects(self::once())
            ->method('getType')
            ->willReturn($type);

        return $argument;
    }

    private function createConstraintViolation(): MockObject&ConstraintViolationInterface
    {
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getMessage')
            ->willReturn('message');
        $violation->method('getPropertyPath')
            ->willReturn('propertyPath');

        return $violation;
    }

    private function createRequest(array $parameters = []): Request
    {
        return Request::create('/', parameters: $parameters);
    }

    private function createResolver(?ConstraintViolationListInterface $violationList = null): DataQueryValueResolver
    {
        $validator = $this->createMock(ValidatorInterface::class);
        if ($violationList instanceof ConstraintViolationListInterface) {
            $validator->expects(self::once())
                ->method('validate')
                ->willReturn($violationList);
        }

        return new DataQueryValueResolver('/', $validator);
    }
}
