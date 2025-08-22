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

use App\Entity\CalculationState;
use App\Repository\AbstractRepository;
use App\Repository\CalculationStateRepository;
use App\Service\IndexService;
use App\Table\CalculationStateTable;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @extends EntityTableTestCase<CalculationState, CalculationStateRepository, CalculationStateTable>
 */
class CalculationStateTableTest extends EntityTableTestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Error
     */
    public function testFormatCalculations(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturnArgument(0);
        $table = new CalculationStateTable(
            $this->createMock(CalculationStateRepository::class),
            $twig,
            $this->createMock(IndexService::class)
        );
        $table->setChecker($this->createMock(AuthorizationCheckerInterface::class));
        $table->setTranslator($this->createMockTranslator());

        $expected = 'macros/_cell_table_link.html.twig';
        $actual = $table->formatCalculations(1, ['id' => 1]);
        self::assertSame($expected, $actual);
    }

    public function testFormatEditable(): void
    {
        $table = new CalculationStateTable(
            $this->createMock(CalculationStateRepository::class),
            $this->createMock(Environment::class),
            $this->createMock(IndexService::class)
        );
        $table->setChecker($this->createMock(AuthorizationCheckerInterface::class));
        $table->setTranslator($this->createMockTranslator());

        $actual = $table->formatEditable(true);
        self::assertSame('common.value_true', $actual);

        $actual = $table->formatEditable(false);
        self::assertSame('common.value_false', $actual);
    }

    #[\Override]
    protected function createEntities(): array
    {
        $entityEditable = [
            'id' => 1,
            'code' => 'code1',
            'description' => 'description1',
            'editable' => true,
            'color' => '#000000',
            'calculations' => 0,
        ];
        $entityNotEditable = [
            'id' => 2,
            'code' => 'code2',
            'description' => 'description2',
            'editable' => false,
            'color' => '#000000',
            'calculations' => 10,
        ];

        return [$entityEditable, $entityNotEditable];
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CalculationStateRepository
    {
        $repository = $this->createMock(CalculationStateRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param CalculationStateRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): CalculationStateTable
    {
        $twig = $this->createMock(Environment::class);
        $translator = $this->createMockTranslator();
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $service = $this->createMockIndexService();

        $table = new CalculationStateTable($repository, $twig, $service);
        $table->setTranslator($translator);
        $table->setChecker($checker);

        return $table;
    }
}
