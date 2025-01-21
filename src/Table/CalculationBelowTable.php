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

namespace App\Table;

use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Twig\Environment;

/**
 * The calculation table for the margin below.
 */
class CalculationBelowTable extends CalculationTable implements \Countable
{
    public function __construct(
        CalculationRepository $repository,
        CalculationStateRepository $stateRepository,
        Environment $twig,
        private readonly ApplicationService $service
    ) {
        parent::__construct($repository, $stateRepository, $twig);
    }

    /**
     * @return int<0, max>
     *
     * @throws ORMException
     */
    public function count(): int
    {
        return $this->getRepository()->countItemsBelow($this->getMinMargin());
    }

    /**
     * @throws ORMException
     */
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'below.empty' : null;
    }

    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return CalculationRepository::addBelowFilter(
            parent::createQueryBuilder($alias),
            $this->getMinMargin(),
            $alias
        );
    }

    protected function getDropDownValues(): array
    {
        return $this->stateRepository->getDropDownBelow($this->getMinMargin());
    }

    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addCustomData('min_margin', $this->getMinMargin());
        }
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    private function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }
}
