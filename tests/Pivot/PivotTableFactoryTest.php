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

namespace App\Tests\Pivot;

use App\Entity\Calculation;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotField;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class PivotTableFactoryTest extends TestCase
{
    /**
     * @psalm-suppress InvalidArgument
     */
    public function testCheckFieldsInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $factory = PivotTableFactory::instance([]);
        /* @phpstan-ignore argument.type */
        $factory->setColumnFields([new Calculation()]);
    }

    public function testConstructor(): void
    {
        $factory = PivotTableFactory::instance([]);
        self::assertNull($factory->getTitle());
        self::assertSame(SumAggregator::class, $factory->getAggregatorClass());
        self::assertNull($factory->getDataField());
        self::assertNull($factory->getKeyField());
        self::assertSame([], $factory->getDataset());
        self::assertSame([], $factory->getColumnFields());
        self::assertSame([], $factory->getRowFields());
        self::assertFalse($factory->isValid());
        self::assertNull($factory->create());
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testConstructorInvalidAggregator(): void
    {
        self::expectException(\InvalidArgumentException::class);
        /* @phpstan-ignore argument.type */
        new PivotTableFactory([], PivotTable::class);
    }

    public function testCreate(): void
    {
        $factory = $this->createFactory();
        self::assertTrue($factory->isValid());
        $table = $factory->create();
        self::assertNotNull($table);
    }

    public function testSetColumnFields(): void
    {
        $field = new PivotField('key');
        $factory = PivotTableFactory::instance([]);
        self::assertSame([], $factory->getColumnFields());
        $factory->setColumnFields($field);
        self::assertSame([$field], $factory->getColumnFields());
    }

    public function testSetDataField(): void
    {
        $field = new PivotField('key');
        $factory = PivotTableFactory::instance([]);
        self::assertNull($factory->getDataField());
        $factory->setDataField($field);
        self::assertSame($field, $factory->getDataField());
    }

    public function testSetKeyField(): void
    {
        $field = new PivotField('key');
        $factory = PivotTableFactory::instance([]);
        self::assertNull($factory->getKeyField());
        $factory->setKeyField($field);
        self::assertSame($field, $factory->getKeyField());
    }

    public function testSetRowFields(): void
    {
        $field = new PivotField('key');
        $factory = PivotTableFactory::instance([]);
        self::assertSame([], $factory->getRowFields());
        $factory->setRowFields($field);
        self::assertSame([$field], $factory->getRowFields());
    }

    public function testSetTitle(): void
    {
        $expected = 'Title';
        $factory = PivotTableFactory::instance([]);
        self::assertNull($factory->getTitle());
        $factory->setTitle($expected);
        self::assertSame($expected, $factory->getTitle());
    }

    /**
     * @phpstan-return array<array<array-key, mixed>>
     */
    private function createDataset(): array
    {
        $row1 = [
            'id' => 1,
            'date' => new DatePoint('2024-05-10'),
            'state' => 'State 1',
            'group' => 'Group 1',
            'category' => 'Category 1',
            'amount' => 25.0,
        ];
        $row2 = [
            'id' => 1,
            'date' => new DatePoint('2024-05-10'),
            'state' => 'State 2',
            'group' => 'Group 2',
            'category' => 'Category 2',
            'amount' => 125.0,
        ];

        return [$row1, $row2];
    }

    /**
     * @phpstan-ignore missingType.generics
     */
    private function createFactory(): PivotTableFactory
    {
        $columns = [
            PivotFieldFactory::year('date', 'year'),
            PivotFieldFactory::semester('date', 'Semester'),
            PivotFieldFactory::quarter('date', 'quarter'),
            PivotFieldFactory::month('date', 'month'),
        ];
        $rows = [
            PivotFieldFactory::default('state', 'State'),
            PivotFieldFactory::default('group', 'Group'),
            PivotFieldFactory::default('category', 'Category'),
        ];
        $keyField = PivotFieldFactory::integer('id', 'id');
        $data = PivotFieldFactory::float('amount', 'Amount');
        $dataset = $this->createDataset();

        return PivotTableFactory::instance($dataset, SumAggregator::class, 'Title')
            ->setColumnFields($columns)
            ->setRowFields($rows)
            ->setKeyField($keyField)
            ->setDataField($data);
    }
}
