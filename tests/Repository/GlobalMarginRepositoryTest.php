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

use App\Entity\GlobalMargin;
use App\Repository\GlobalMarginRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\GlobalMarginTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\MappingException;

class GlobalMarginRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use GlobalMarginTrait;

    private GlobalMarginRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(GlobalMarginRepository::class);
    }

    public function testCreateDefaultQueryBuilder(): void
    {
        $builder = $this->repository->createDefaultQueryBuilder();
        $actual = $builder->getRootAliases();
        self::assertSame('e', $actual[0]);

        $builder = $this->repository->createDefaultQueryBuilder('source');
        $actual = $builder->getRootAliases();
        self::assertSame('source', $actual[0]);
    }

    public function testFindByMinimum(): void
    {
        $actual = $this->repository->findByMinimum();
        self::assertEmpty($actual);

        try {
            $this->getGlobalMargin();
            $actual = $this->repository->findByMinimum();
            self::assertCount(1, $actual);
        } finally {
            $this->deleteGlobalMargin();
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDefaultOrder(): void
    {
        $actual = $this->repository->getDefaultOrder();
        self::assertSame([], $actual);
    }

    public function testGetDistinctValues(): void
    {
        $actual = $this->repository->getDistinctValues('minimum');
        self::assertEmpty($actual);

        $actual = $this->repository->getDistinctValues('minimum', '0');
        self::assertEmpty($actual);

        $actual = $this->repository->getDistinctValues('minimum', '0', 15);
        self::assertEmpty($actual);
    }

    public function testGetMargin(): void
    {
        $actual = $this->repository->getMargin(0.0);
        self::assertSame(0.0, $actual);

        try {
            $this->getGlobalMargin();
            $actual = $this->repository->getMargin(0.0);
            self::assertSame(1.1, $actual);
        } finally {
            $this->deleteGlobalMargin();
        }
    }

    public function testGetSearchFields(): void
    {
        $actual = $this->repository->getSearchFields('field');
        self::assertSame('e.field', $actual);

        $actual = $this->repository->getSearchFields('field', 'source');
        self::assertSame('source.field', $actual);
    }

    public function testGetSearchQuery(): void
    {
        $sortedFields = [
            'minimum' => 'ASC',
        ];
        $criteria = [
            'minimum > 0',
            Criteria::create(true),
        ];
        $this->repository->getSearchQuery($sortedFields, $criteria);
        self::expectNotToPerformAssertions();
    }

    /**
     * @throws MappingException
     */
    public function testGetSingleIdentifierFieldName(): void
    {
        $actual = $this->repository->getSingleIdentifierFieldName();
        self::assertSame('id', $actual);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField('field');
        self::assertSame('e.field', $actual);

        $actual = $this->repository->getSortField('field', 'source');
        self::assertSame('source.field', $actual);
    }

    public function testPersist(): void
    {
        $margin = new GlobalMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setMargin(1.1);

        try {
            $this->repository->persist($margin);
            $actual = $this->repository->findAll();
            self::assertCount(1, $actual);
        } finally {
            $this->repository->remove($margin);
        }
    }
}
