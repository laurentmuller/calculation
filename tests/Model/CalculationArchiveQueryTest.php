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

namespace App\Tests\Model;

use App\Entity\CalculationState;
use App\Model\CalculationArchiveQuery;
use App\Tests\DateAssertTrait;
use App\Tests\Entity\IdTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class CalculationArchiveQueryTest extends TestCase
{
    use DateAssertTrait;
    use IdTrait;

    public function testConstruct(): void
    {
        $query = new CalculationArchiveQuery();
        self::assertSame([], $query->getSources());
        self::assertSame([], $query->getSourcesId());
        self::assertSame('', $query->getSourcesCode());
        self::assertNull($query->getTarget());
        self::assertNull($query->getTargetId());
        self::assertNull($query->getTargetCode());
    }

    public function testDate(): void
    {
        $date = DateUtils::sub(DateUtils::createDate(), 'P6M');
        $query = new CalculationArchiveQuery();
        self::assertTimestampEquals($date, $query->getDate());

        $date = DateUtils::sub(DateUtils::createDate(), 'P6D');
        $query->setDate($date);
        self::assertSame($date, $query->getDate());

        $expected = FormatUtils::formatDate($date);
        self::assertSame($expected, $query->getDateFormatted());
    }

    public function testSimulate(): void
    {
        $query = new CalculationArchiveQuery();
        self::assertTrue($query->isSimulate());
        $query->setSimulate(false);
        self::assertFalse($query->isSimulate());
    }

    public function testSources(): void
    {
        $query = new CalculationArchiveQuery();
        $source1 = $this->createState(1, 'code1', true);
        $source2 = $this->createState(2, 'code2', true);
        $sources = [$source1, $source2];
        $query->setSources($sources);

        self::assertSame($sources, $query->getSources());
        self::assertSame([1, 2], $query->getSourcesId());
        self::assertSame('code1, code2', $query->getSourcesCode());
    }

    public function testTarget(): void
    {
        $query = new CalculationArchiveQuery();
        $target = $this->createState(1, 'code', true);
        $query->setTarget($target);

        self::assertSame($target, $query->getTarget());
        self::assertSame($target->getId(), $query->getTargetId());
        self::assertSame($target->getCode(), $query->getTargetCode());
    }

    private function createState(int $id, string $code, bool $editable): CalculationState
    {
        $state = new CalculationState();
        $state->setCode($code)
            ->setEditable($editable);

        return self::setId($state, $id);
    }
}
