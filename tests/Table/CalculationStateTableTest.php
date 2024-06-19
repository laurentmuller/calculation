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
use App\Table\AbstractEntityTable;
use App\Table\AbstractTable;
use App\Table\CalculationStateTable;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<CalculationState, CalculationStateRepository, CalculationStateTable>
 */
#[CoversClass(AbstractTable::class)]
#[CoversClass(AbstractEntityTable::class)]
#[CoversClass(CalculationStateTable::class)]
class CalculationStateTableTest extends EntityTableTestCase
{
    use TranslatorMockTrait;

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

    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CalculationStateRepository
    {
        $repository = $this->createMock(CalculationStateRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param CalculationStateRepository $repository
     *
     * @throws Exception
     */
    protected function createTable(AbstractRepository $repository): CalculationStateTable
    {
        $twig = $this->createMock(Environment::class);
        $translator = $this->createMockTranslator();
        $checker = $this->createMock(AuthorizationCheckerInterface::class);

        $table = new CalculationStateTable($repository, $twig);
        $table->setTranslator($translator);
        $table->setChecker($checker);

        return $table;
    }
}
