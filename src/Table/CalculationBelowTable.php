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
use Doctrine\ORM\QueryBuilder;
use Twig\Environment;

/**
 * The calculation table for margin below.
 */
class CalculationBelowTable extends CalculationTable implements \Countable
{
    /**
     * Constructor.
     */
    public function __construct(CalculationRepository $repository, CalculationStateRepository $stateRepository, Environment $twig, private readonly ApplicationService $service)
    {
        parent::__construct($repository, $stateRepository, $twig);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        /** @var CalculationRepository $repository */
        $repository = $this->repository;

        return $repository->countBelowItems($this->getMinMargin());
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyMessage(): string
    {
        return 'below.empty';
    }

    /**
     * {@inheritDoc}
     */
    public function isEmptyAllowed(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        /** @var CalculationRepository $repository */
        $repository = $this->repository;

        $builder = parent::createDefaultQueryBuilder($alias);

        return $repository->addBelowFilter($builder, $this->getMinMargin());
    }

    /**
     * {@inheritDoc}
     */
    protected function getCalculationStates(): array
    {
        $margin = $this->getMinMargin();

        return $this->stateRepository->getListCountBelows($margin);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addCustomData('min_margin', $this->getMinMargin());
        }
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getMinMargin(): float
    {
        return $this->service->getMinMargin();
    }
}
