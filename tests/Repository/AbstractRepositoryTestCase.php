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

namespace App\Tests\Repository;

use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;

/**
 * @template TEntity of EntityInterface
 * @template TRepository of AbstractRepository<TEntity>
 */
abstract class AbstractRepositoryTestCase extends KernelServiceTestCase
{
    use DatabaseTrait;

    /**
     * @var TRepository
     */
    protected AbstractRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService($this->getRepositoryClass());
    }

    public function assertSameSearchField(
        string $field,
        string $expected,
        string $alias = AbstractRepository::DEFAULT_ALIAS
    ): void {
        $actual = $this->repository->getSearchFields($field, $alias);
        self::assertSame($expected, $actual);
    }

    public function assertSameSortField(
        string $field,
        string $expected,
        string $alias = AbstractRepository::DEFAULT_ALIAS
    ): void {
        $actual = $this->repository->getSortField($field, $alias);
        self::assertSame($expected, $actual);
    }

    /**
     * @return class-string<TRepository>
     */
    abstract protected function getRepositoryClass(): string;
}
