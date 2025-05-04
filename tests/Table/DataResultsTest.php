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

namespace App\Tests\Table;

use App\Interfaces\TableInterface;
use App\Table\DataResults;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DataResultsTest extends TestCase
{
    public function testAttribute(): void
    {
        $results = new DataResults();
        self::assertSame([], $results->attributes);
        $results->addAttribute('name', 'value');

        /** @phpstan-var array<string, mixed> $actual */
        $actual = $results->attributes;
        self::assertCount(1, $actual);
        self::assertSame(['name' => 'value'], $actual);
    }

    public function testCustomData(): void
    {
        $results = new DataResults();
        self::assertSame([], $results->customData);
        $results->addCustomData('name', 'value');

        /** @phpstan-var array<string, mixed> $actual */
        $actual = $results->customData;
        self::assertCount(1, $actual);
        self::assertSame(['name' => 'value'], $actual);
        self::assertSame('value', $results->getCustomData('name'));
        self::assertNull($results->getCustomData('fake'));
    }

    public function testDefaultValues(): void
    {
        $actual = new DataResults();
        self::assertSame([], $actual->attributes);
        self::assertSame([], $actual->columns);
        self::assertSame([], $actual->customData);
        self::assertSame(0, $actual->filtered);
        self::assertSame(TableInterface::PAGE_LIST, $actual->pageList);
        self::assertSame([], $actual->params);
        self::assertSame([], $actual->rows);
        self::assertSame(Response::HTTP_OK, $actual->status);
        self::assertSame(0, $actual->totalNotFiltered);
    }

    public function testJsonSerialize(): void
    {
        $results = new DataResults();
        $actual = $results->jsonSerialize();
        self::assertSame(0, $actual[TableInterface::PARAM_TOTAL_NOT_FILTERED]);
        self::assertSame(0, $actual[TableInterface::PARAM_TOTAL]);
        self::assertSame([], $actual[TableInterface::PARAM_ROWS]);
    }

    public function testParameter(): void
    {
        $results = new DataResults();
        self::assertSame([], $results->params);
        $results->addParameter('name', 'value');

        /** @phpstan-var array<string, mixed> $actual */
        $actual = $results->params;
        self::assertCount(1, $actual);
        self::assertSame(['name' => 'value'], $actual);
        self::assertSame('value', $results->getParameter('name'));
        self::assertSame('value', $results->getParameter('fake', 'value'));
        self::assertNull($results->getParameter('fake'));
    }

    public function testSetStatus(): void
    {
        $actual = new DataResults();
        self::assertSame(Response::HTTP_OK, $actual->status);
        $actual->setStatus(Response::HTTP_ACCEPTED);
        self::assertSame(Response::HTTP_ACCEPTED, $actual->status);
    }
}
